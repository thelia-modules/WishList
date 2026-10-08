<?php

/*
 * This file is part of the Thelia package.
 * http://www.thelia.net
 *
 * (c) OpenStudio <info@thelia.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WishList\Service;

use Cocur\Slugify\Slugify;
use Propel\Runtime\ActiveQuery\Criteria;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Thelia\Core\Event\Cart\CartEvent;
use Thelia\Core\Event\TheliaEvents;
use Thelia\Core\Security\SecurityContext;
use Thelia\Core\Translation\Translator;
use Thelia\Log\Tlog;
use Thelia\Model\ConfigQuery;
use Thelia\Model\Lang;
use Thelia\Model\Product;
use Thelia\Model\Map\ProductSaleElementsTableMap;
use Thelia\Model\ProductSaleElementsQuery;
use WishList\Model\Map\WishListProductTableMap;
use WishList\Dto\RejectedWishListProduct;
use WishList\Dto\RejectionReason;
use WishList\Exception\DuplicateWishListTitleException;
use WishList\Model\WishList;
use WishList\Model\WishListProduct;
use WishList\Model\WishListProductQuery;
use WishList\Model\WishListQuery;
use WishList\WishList as WishListModule;

class WishListService
{
    public const DEFAULT_WISH_LIST_TITLE = 'Default';

    protected $securityContext;
    protected $requestStack;
    protected $eventDispatcher;

    public function __construct(RequestStack $requestStack, SecurityContext $securityContext, EventDispatcherInterface $eventDispatcher)
    {
        $this->securityContext = $securityContext;
        $this->requestStack = $requestStack;
        $this->eventDispatcher = $eventDispatcher;
    }

    public function addProduct($pseId, $quantity, $wishListId = null)
    {
        try {
            $wishList = $this->findWishListOrCreateDefault($wishListId);

            if (null === $wishList) {
                throw new \Exception(Translator::getInstance()->trans('There is no wishlist with this id for this customer', [], WishListModule::DOMAIN_NAME));
            }

            // A null or non-positive quantity means the customer wants the line gone.
            if ((int) $quantity <= 0) {
                $this->removeProduct($pseId, $wishList->getId());

                return $wishList->getId();
            }

            $productWishList = WishListProductQuery::create()
                ->filterByProductSaleElementsId($pseId)
                ->filterByWishListId($wishList->getId())
                ->findOneOrCreate();

            $productWishList
                ->setQuantity($quantity)
                ->save();
        } catch (\Exception $e) {
            Tlog::getInstance()->error('Error during wishlist add :' . $e->getMessage());

            return false;
        }

        return $wishList->getId();
    }

    public function removeProduct($pseId, $wishListId = null)
    {
        try {
            $wishList = $this->findWishListOrCreateDefault($wishListId);

            if (null === $wishList) {
                throw new \Exception(Translator::getInstance()->trans('There is no wishlist with this id for this customer', [], WishListModule::DOMAIN_NAME));
            }

            [$customerId, $sessionId] = $this->getCurrentUserOrSession();

            $productWishList = WishListProductQuery::getExistingObject($wishList->getId(), $customerId, $sessionId, $pseId);

            if ($productWishList) {
                $productWishList->delete();
            }
        } catch (\Exception $e) {
            Tlog::getInstance()->error('Error during wishlist remove :' . $e->getMessage());

            return false;
        }

        return true;
    }

    public function inWishList($pseId, $wishListId): bool
    {
        [$customerId, $sessionId] = $this->getCurrentUserOrSession();

        if (null === $customerId && null === $sessionId) {
            return false;
        }

        return null !== WishListProductQuery::getExistingObject($wishListId, $customerId, $sessionId, $pseId);
    }

    /**
     * Returns every wishlist of the current customer (or session) that contains the given PSE.
     *
     * @return \Propel\Runtime\Collection\ObjectCollection|WishList[]
     */
    public function getWishListsContainingPse($pseId)
    {
        [$customerId, $sessionId] = $this->getCurrentUserOrSession();

        $query = WishListQuery::create()
            ->useWishListProductQuery()
                ->filterByProductSaleElementsId($pseId)
            ->endUse()
            ->distinct();

        if (null === $customerId && null === $sessionId) {
            return $query->where('1 = 0')->find();
        }

        if (null !== $customerId) {
            $query->filterByCustomerId($customerId);
        }

        if (null !== $sessionId) {
            $query->filterBySessionId($sessionId);
        }

        return $query->find();
    }

    /**
     * Every wish list of the current owner holding at least one sale element of the product.
     *
     * @return \Propel\Runtime\Collection\ObjectCollection|WishList[]
     */
    public function getWishListsContainingProduct(int $productId)
    {
        [$customerId, $sessionId] = $this->getCurrentUserOrSession();

        $query = WishListQuery::create()
            ->useWishListProductQuery()
                ->useProductSaleElementsQuery()
                    ->filterByProductId($productId)
                ->endUse()
            ->endUse()
            ->distinct();

        if (null === $customerId && null === $sessionId) {
            return $query->where('1 = 0')->find();
        }

        if (null !== $customerId) {
            $query->filterByCustomerId($customerId);
        }

        if (null !== $sessionId) {
            $query->filterBySessionId($sessionId);
        }

        return $query->find();
    }

    /**
     * The visible products of each list of the current owner, read in one query.
     *
     * @return array<int, list<int>> product ids keyed by wish list id
     */
    public function getVisibleProductIdsByWishList(): array
    {
        [$customerId, $sessionId] = $this->getCurrentUserOrSession();

        if (null === $customerId && null === $sessionId) {
            return [];
        }

        $rows = WishListProductQuery::create()
            ->useWishListQuery()
                ->_if(null !== $customerId)
                    ->filterByCustomerId($customerId)
                ->_else()
                    ->filterBySessionId($sessionId)
                ->_endif()
            ->endUse()
            ->useProductSaleElementsQuery()
                ->useProductQuery()
                    ->filterByVisible(1)
                ->endUse()
            ->endUse()
            ->orderById()
            ->select([WishListProductTableMap::COL_WISH_LIST_ID, ProductSaleElementsTableMap::COL_PRODUCT_ID])
            ->find();

        $productIds = [];

        foreach ($rows as $row) {
            $wishListId = (int) $row[WishListProductTableMap::COL_WISH_LIST_ID];
            $productId = (int) $row[ProductSaleElementsTableMap::COL_PRODUCT_ID];

            $productIds[$wishListId][$productId] = $productId;
        }

        return array_map(array_values(...), $productIds);
    }

    /**
     * Removes every sale element of the product from one list of the current owner.
     */
    public function removeProductFromWishList(int $productId, int $wishListId): bool
    {
        [$customerId, $sessionId] = $this->getCurrentUserOrSession();

        $wishList = $this->getWishListObject($wishListId, $customerId, $sessionId);

        if (null === $wishList) {
            return false;
        }

        WishListProductQuery::create()
            ->filterByWishListId($wishList->getId())
            ->useProductSaleElementsQuery()
                ->filterByProductId($productId)
            ->endUse()
            ->find()
            ->delete();

        return true;
    }

    /**
     * The sale element a product goes into a list with when the customer picks the product
     * itself (a product card): its default one, else its first one.
     */
    public function findDefaultProductSaleElementsId(int $productId): ?int
    {
        $productSaleElements = ProductSaleElementsQuery::create()
            ->filterByProductId($productId)
            ->orderByIsDefault(Criteria::DESC)
            ->orderById()
            ->findOne();

        return $productSaleElements?->getId();
    }

    public function isPseInAnyWishList($pseId): bool
    {
        return $this->getWishListsContainingPse($pseId)->count() > 0;
    }

    public function getWishList($wishListId)
    {
        [$customerId, $sessionId] = $this->getCurrentUserOrSession();

        return $this->getWishListObject($wishListId, $customerId, $sessionId);
    }

    public function getAllWishLists()
    {
        [$customerId, $sessionId] = $this->getCurrentUserOrSession();

        $wishList = WishListQuery::create();

        // Without an owner, every list of the shop would be returned.
        if (null === $customerId && null === $sessionId) {
            return $wishList->where('1 = 0')->find();
        }

        if (null !== $customerId) {
            $wishList->filterByCustomerId($customerId);
        }

        if (null !== $sessionId) {
            $wishList->filterBySessionId($sessionId);
        }

        return $wishList->find();
    }

    public function clearWishList($wishListId)
    {
        [$customerId, $sessionId] = $this->getCurrentUserOrSession();

        if (null === $customerId && null === $sessionId) {
            return false;
        }

        $query = WishListProductQuery::create()
            ->useWishListQuery()
            ->filterById($wishListId)
            ->endUse();

        if (null !== $customerId) {
            $query
                ->useWishListQuery()
                ->filterByCustomerId($customerId)
                ->endUse();
        }

        if (null !== $sessionId) {
            $query
                ->useWishListQuery()
                ->filterBySessionId($sessionId)
                ->endUse();
        }

        $query->find()->delete();

        return true;
    }

    public function findWishListOrCreateDefault($wishListId = null)
    {
        [$customerId, $sessionId] = $this->getCurrentUserOrSession();

        // Nobody to give the list to: creating one would leave an orphan any caller could reach.
        if (null === $customerId && null === $sessionId) {
            return null;
        }

        if ($wishListId) {
            return $this->getWishList($wishListId);
        }
        $defaultWishList = $this->findCurrentDefaultWishList();
        if (null !== $defaultWishList) {
            return $defaultWishList;
        }

        // The owner may already own an untagged list bearing the default title: reuse it
        // rather than letting the title uniqueness check reject the implicit creation.
        $existingDefault = $this->findOwnedWishListByTitle(self::DEFAULT_WISH_LIST_TITLE);
        if (null !== $existingDefault) {
            return $existingDefault;
        }

        return $this->createUpdateWishList(self::DEFAULT_WISH_LIST_TITLE);
    }

    public function setWishListToDefault($wishListId): void
    {
        $newDefaultWishList = $this->getWishList($wishListId);
        if (null === $newDefaultWishList) {
            throw new \Exception(Translator::getInstance()->trans('There is no wishlist with this id for this customer', [], WishListModule::DOMAIN_NAME));
        }

        [$customerId, $sessionId] = $this->getCurrentUserOrSession();
        $wishList = WishListQuery::create();
        if (null !== $customerId) {
            $wishList->filterByCustomerId($customerId);
        }
        if (null !== $sessionId) {
            $wishList->filterBySessionId($sessionId);
        }
        $wishList->update(['Default' => 0]);

        $newDefaultWishList->setDefault(1)->save();
    }

    /**
     * @throws DuplicateWishListTitleException when the owner already uses this title on another list
     */
    public function createUpdateWishList($title, $products = null, $wishListId = null)
    {
        [$customerId, $sessionId] = $this->getCurrentUserOrSession();

        if (null === $customerId && null === $sessionId) {
            throw new \RuntimeException('A wish list needs a customer or a session to belong to.');
        }

        $this->assertTitleIsAvailable($title, $wishListId);

        $rewrittenUrl = null;
        if (null === $wishList = $this->getWishListObject($wishListId, $customerId, $sessionId)) {
            $wishList = new WishList();
            $defaultWishList = $this->findCurrentDefaultWishList();
            if (null !== $customerId) {
                $wishList->setCustomerId($customerId);
            }

            if (null !== $sessionId) {
                $wishList->setSessionId($sessionId);
            }

            if (null !== $title) {
                $wishList->setTitle($title);
            }

            $hash = $this->createWishlistSlug($wishList);

            $wishList->setCode($hash);
            if (null === $defaultWishList) {
                $wishList->setDefault(1);
            }
        }

        if (null !== $title) {
            $wishList->setTitle($title);
        }

        $wishList->save();

        $rewrittenUrl = $this->createWishListUrl($wishList);
        if (null !== $rewrittenUrl) {
            $request = $this->requestStack->getCurrentRequest();
            $currentLang = (null !== $request && $request->hasSession())
                ? $request->getSession()->get('thelia.current.lang')
                : Lang::getDefaultLanguage();
            $wishList
                ->setRewrittenUrl($currentLang->getLocale(), $rewrittenUrl)
                ->save();
        }

        if (null !== $products) {
            foreach ($products as $product) {
                $this->addProduct($product['productSaleElementId'], $product['quantity'], $wishList->getId());
            }
        }

        return $wishList;
    }

    public function createWishListUrl(Wishlist $wishlist)
    {
        $wishListSlugBase = $wishlist->getTitle().'-'.$wishlist->getId();

        return Slugify::create()->slugify($wishListSlugBase, '-');
    }

    public function deleteWishList($wishListId): void
    {
        [$customerId, $sessionId] = $this->getCurrentUserOrSession();

        if (null !== $wishList = $this->getWishListObject($wishListId, $customerId, $sessionId)) {
            $wishList->delete();
        }
    }

    /**
     * @throws DuplicateWishListTitleException when the owner already uses this title on another list
     */
    public function duplicateWishList($wishListId, $title)
    {
        $this->assertTitleIsAvailable($title);

        [$customerId, $sessionId] = $this->getCurrentUserOrSession();
        /** @var Lang $currentLang */
        $request = $this->requestStack->getCurrentRequest();
        $currentLang = (null !== $request && $request->hasSession())
            ? $request->getSession()->get('thelia.current.lang')
            : Lang::getDefaultLanguage();

        $wishList = $this->getWishListObject($wishListId, $customerId, $sessionId);

        $newWishList = (new WishList())
            ->setTitle($title)
            ->setCustomerId($customerId)
            ->setSessionId($sessionId);

        $code = $this->createWishlistSlug($newWishList);

        $newWishList
            ->setCode($code)
            ->save()
        ;

        $rewrittenUrl = $this->createWishListUrl($newWishList);
        $newWishList
            ->setRewrittenUrl($currentLang->getLocale(), $rewrittenUrl)
            ->save();

        foreach ($wishList->getWishListProducts() as $wishListProduct) {
            $this->addProduct($wishListProduct->getProductSaleElementsId(), $wishListProduct->getQuantity(), $newWishList->getId());
        }

        return $newWishList;
    }

    public function moveSessionListsToCustomer(string $sessionId, int $customerId): void
    {
        foreach (WishListQuery::create()->filterBySessionId($sessionId)->find() as $wishList) {
            $wishList
                ->setCustomerId($customerId)
                ->setSessionId(null)
                ->save();
        }
    }

    public function sessionToUser($sessionId): void
    {
        $customer = $this->securityContext->getCustomerUser();
        $wishLists = WishListQuery::create()->filterBySessionId($sessionId)->find();

        foreach ($wishLists as $wishList) {
            $wishList
                ->setCustomerId($customer->getId())
                ->setSessionId(null)
                ->save();
        }
    }

    /**
     * @return RejectedWishListProduct[] lines that were skipped or capped, for the caller to display
     */
    public function addWishListToCart($wishListId): array
    {
        [$customerId, $sessionId] = $this->getCurrentUserOrSession();

        $wishList = $this->getWishListObject($wishListId, $customerId, $sessionId);

        if (null === $wishList) {
            return [];
        }

        return $this->addWishlistProductsToCart($wishList);
    }

    /**
     * @return RejectedWishListProduct[] lines that were skipped or capped, for the caller to display
     */
    public function createCartFromWishlist($wishListId): array
    {
        [$customerId, $sessionId] = $this->getCurrentUserOrSession();

        $wishList = $this->getWishListObject($wishListId, $customerId, $sessionId);

        if (null === $wishList) {
            return [];
        }

        // Store a new empty cart in the session.
        $request = $this->requestStack->getCurrentRequest();
        if (null !== $request && $request->hasSession()) {
            $request->getSession()->clearSessionCart($this->eventDispatcher);
        }

        return $this->addWishlistProductsToCart($wishList);
    }

    /**
     * @return RejectedWishListProduct[]
     */
    private function addWishlistProductsToCart(WishList $wishList): array
    {
        $request = $this->requestStack->getCurrentRequest();
        if (null === $request || !$request->hasSession()) {
            return [];
        }
        $cart = $request->getSession()->getSessionCart($this->eventDispatcher);

        $checkStock = ConfigQuery::checkAvailableStock();
        $rejected = [];

        foreach ($wishList->getWishListProducts() as $wishListProduct) {
            $rejection = $this->rejectionFor($wishListProduct, $checkStock);
            $quantity = $rejection?->acceptedQuantity ?? (int) $wishListProduct->getQuantity();

            if (null !== $rejection) {
                $rejected[] = $rejection;
            }

            if ($quantity <= 0) {
                continue;
            }

            $event = new CartEvent($cart);
            $event
                ->setProductId($wishListProduct->getProductSaleElements()->getProductId())
                ->setProductSaleElementsId($wishListProduct->getProductSaleElementsId())
                ->setQuantity($quantity)
                ->setAppend(true)
                ->setNewness(true)
            ;

            $this->eventDispatcher->dispatch($event, TheliaEvents::CART_ADDITEM);
        }

        return $rejected;
    }

    /**
     * Describes a wish list line that cannot reach the cart as requested. Returning null
     * means the whole requested quantity is accepted.
     *
     * Stock is only taken into account when the shop checks availability and the product is
     * not virtual, which mirrors what the core does on cart quantity updates.
     */
    private function rejectionFor(WishListProduct $wishListProduct, bool $checkStock): ?RejectedWishListProduct
    {
        $requestedQuantity = (int) $wishListProduct->getQuantity();

        $productSaleElements = $wishListProduct->getProductSaleElements();

        if (null === $productSaleElements) {
            return new RejectedWishListProduct(
                (int) $wishListProduct->getProductSaleElementsId(),
                null,
                null,
                $requestedQuantity,
                0,
                RejectionReason::ProductSaleElementMissing
            );
        }

        $product = $productSaleElements->getProduct();
        $productId = $productSaleElements->getProductId();
        $productTitle = $this->productTitle($product);

        if (null === $product || 1 !== (int) $product->getVisible()) {
            return new RejectedWishListProduct(
                (int) $wishListProduct->getProductSaleElementsId(),
                $productId,
                $productTitle,
                $requestedQuantity,
                0,
                RejectionReason::ProductNotVisible
            );
        }

        if (!$checkStock || 1 === (int) $product->getVirtual()) {
            return null;
        }

        $availableQuantity = (int) $productSaleElements->getQuantity();

        if ($availableQuantity <= 0) {
            return new RejectedWishListProduct(
                (int) $wishListProduct->getProductSaleElementsId(),
                $productId,
                $productTitle,
                $requestedQuantity,
                0,
                RejectionReason::OutOfStock
            );
        }

        $quantity = min($requestedQuantity, $availableQuantity);

        if ($quantity < $requestedQuantity) {
            return new RejectedWishListProduct(
                (int) $wishListProduct->getProductSaleElementsId(),
                $productId,
                $productTitle,
                $requestedQuantity,
                $quantity,
                RejectionReason::StockLimited
            );
        }

        return null;
    }

    public function cloneWishList($wishListId)
    {
        [$customerId, $sessionId] = $this->getCurrentUserOrSession();

        $wishList = WishListQuery::create()->findPk($wishListId);

        /** @var Lang $currentLang */
        $request = $this->requestStack->getCurrentRequest();
        $currentLang = (null !== $request && $request->hasSession())
            ? $request->getSession()->get('thelia.current.lang')
            : Lang::getDefaultLanguage();

        $newWishList = (new WishList())
            ->setTitle($wishList->getTitle())
            ->setCustomerId($customerId)
            ->setSessionId($sessionId);

        $code = $this->createWishlistSlug($newWishList);

        $newWishList
            ->setCode($code)
            ->save();

        $rewrittenUrl = $this->createWishListUrl($newWishList);
        $newWishList
            ->setRewrittenUrl($currentLang->getLocale(), $rewrittenUrl)
            ->save();

        foreach ($wishList->getWishListProducts() as $wishListProduct) {
            $this->addProduct($wishListProduct->getProductSaleElementsId(), $wishListProduct->getQuantity(), $newWishList->getId());
        }

        return $newWishList;
    }

    /**
     * A catalogue is rarely translated in every locale Propel defaults to, so the title is
     * looked up in the locale of the request, then in the shop one, and the reference is
     * kept as a last resort: a rejected line must always be nameable to the customer.
     */
    private function productTitle(?Product $product): ?string
    {
        if (null === $product) {
            return null;
        }

        $request = $this->requestStack->getCurrentRequest();
        $locales = array_filter([$request?->getLocale(), Lang::getDefaultLanguage()->getLocale()]);

        foreach ($locales as $locale) {
            $title = $product->setLocale($locale)->getTitle();

            if (null !== $title && '' !== $title) {
                return $title;
            }
        }

        return $product->getRef();
    }

    protected function getWishListObject($wishListId, $customerId, $sessionId): ?WishList
    {
        // Without an owner the query below would match the list of anybody.
        if (null === $customerId && null === $sessionId) {
            return null;
        }

        $wishList = WishListQuery::create()
            ->filterById($wishListId);

        if (null !== $customerId) {
            $wishList->filterByCustomerId($customerId);
        }

        if (null !== $sessionId) {
            $wishList->filterBySessionId($sessionId);
        }

        return $wishList->findOne();
    }

    /**
     * A title must stay unique for a given owner, whether the owner is an authenticated
     * customer or an anonymous session.
     *
     * @throws DuplicateWishListTitleException
     */
    protected function assertTitleIsAvailable($title, $wishListId = null): void
    {
        if (null === $title || '' === trim((string) $title)) {
            return;
        }

        $duplicate = $this->findOwnedWishListByTitle($title, $wishListId);

        if (null !== $duplicate) {
            throw new DuplicateWishListTitleException(
                Translator::getInstance()->trans('You already have a wishlist with this name', [], WishListModule::DOMAIN_NAME)
            );
        }
    }

    protected function findOwnedWishListByTitle($title, $excludedWishListId = null): ?WishList
    {
        [$customerId, $sessionId] = $this->getCurrentUserOrSession();

        // Without an owner there is nothing to scope the search on: checking against every
        // wish list of the shop would reject unrelated titles.
        if (null === $customerId && null === $sessionId) {
            return null;
        }

        $query = WishListQuery::create()->filterByTitle($title);

        if (null !== $customerId) {
            $query->filterByCustomerId($customerId);
        }

        if (null !== $sessionId) {
            $query->filterBySessionId($sessionId);
        }

        if (null !== $excludedWishListId) {
            $query->filterById($excludedWishListId, Criteria::NOT_EQUAL);
        }

        return $query->findOne();
    }

    protected function findCurrentDefaultWishList()
    {
        [$customerId, $sessionId] = $this->getCurrentUserOrSession();

        if (null === $customerId && null === $sessionId) {
            return null;
        }

        $wishList = WishListQuery::create()
            ->filterByDefault(1);

        if (null !== $customerId) {
            $wishList->filterByCustomerId($customerId);
        }

        if (null !== $sessionId) {
            $wishList->filterBySessionId($sessionId);
        }

        return $wishList->findOne();
    }

    protected function getCurrentUserOrSession()
    {
        $customer = $this->securityContext->getCustomerUser();
        $customerId = $customer?->getId();
        $sessionId = null;
        if (!$customer) {
            $request = $this->requestStack->getCurrentRequest();
            if (null !== $request && $request->hasSession()) {
                // An unstarted session answers an empty id: it owns nothing.
                $sessionId = $request->getSession()->getId() ?: null;
            }
        }

        return [$customerId, $sessionId];
    }

    public function createWishlistSlug(Wishlist $wishlist)
    {
        // Create hash from liste title
        $wishlistHash = Slugify::create()->slugify($wishlist->getTitle(), '-');

        // Manage collisions
        $count = 0;

        while (true) {
            if (
                WishListQuery::create()
                ->filterByCode($wishlistHash)
                ->filterById($wishlist->getId(), Criteria::NOT_EQUAL)
                ->count() === 0
            ) {
                break;
            }

            $wishlistHash = Slugify::create()->slugify($wishlist->getTitle() . '-' . ++$count, '-');
        }

        return $wishlistHash;
    }

    public function isWishListTypeAlreadyExists(WishList $wishList): bool
    {
        [$customerId, $sessionId] = $this->getCurrentUserOrSession();

        if (($customerId !== null && $wishList->getCustomerId() !== $customerId)
            || ($sessionId !== null && $wishList->getSessionId() !== $sessionId)) {
            throw new \Exception('WishList not found for this customer or session');
        }

        $wishListData = array_map(static function($item) {
            return [
                'ProductSaleElementsId' => $item['ProductSaleElementsId'],
                'Quantity' => $item['Quantity']
            ];
        }, $wishList->getWishListProducts()->toArray());

        $currentWishListsType = WishListQuery::create()
            ->filterByCustomerId($wishList->getCustomerId())
            ->filterByIsType(1)
            ->find();
        foreach ($currentWishListsType as $currentWishListType) {
            $currentWishListTypeData = array_map(static function($item) {
                return [
                    'ProductSaleElementsId' => $item['ProductSaleElementsId'],
                    'Quantity' => $item['Quantity']
                ];
            }, $currentWishListType->getWishListProducts()->toArray());

            if ($wishListData === $currentWishListTypeData) {
                return true;
            }
        }

        return false;
    }
}
