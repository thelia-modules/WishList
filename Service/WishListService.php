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
use Thelia\Model\Lang;
use WishList\Exception\DuplicateWishListTitleException;
use WishList\Model\WishList;
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

        if (null !== $customerId) {
            $query->filterByCustomerId($customerId);
        }

        if (null !== $sessionId) {
            $query->filterBySessionId($sessionId);
        }

        return $query->find();
    }

    public function isPseInAnyWishList($pseId): bool
    {
        return $this->getWishListsContainingPse($pseId)->count() > 0;
    }

    public function getWishList($wishListId)
    {
        $customer = $this->securityContext->getCustomerUser();
        $customerId = $customer?->getId();
        $sessionId = null;
        if (!$customer) {
            $request = $this->requestStack->getCurrentRequest();
            if (null !== $request && $request->hasSession()) {
                $sessionId = $request->getSession()->getId();
            }
        }

        return $this->getWishListObject($wishListId, $customerId, $sessionId);
    }

    public function getAllWishLists()
    {
        [$customerId, $sessionId] = $this->getCurrentUserOrSession();

        $wishList = WishListQuery::create();

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

    public function addWishListToCart($wishListId): void
    {
        [$customerId, $sessionId] = $this->getCurrentUserOrSession();

        $wishList = $this->getWishListObject($wishListId, $customerId, $sessionId);

        if (null !== $wishList) {
            $this->addWishlistProductsToCart($wishList);
        }
    }

    public function createCartFromWishlist($wishListId): void
    {
        [$customerId, $sessionId] = $this->getCurrentUserOrSession();

        $wishList = $this->getWishListObject($wishListId, $customerId, $sessionId);

        if (null !== $wishList) {
            // Store a new empty cart in the session.
            $request = $this->requestStack->getCurrentRequest();
            if (null !== $request && $request->hasSession()) {
                $request->getSession()->clearSessionCart($this->eventDispatcher);
            }

            $this->addWishlistProductsToCart($wishList);
        }
    }

    private function addWishlistProductsToCart(WishList $wishList): void
    {
        $request = $this->requestStack->getCurrentRequest();
        if (null === $request || !$request->hasSession()) {
            return;
        }
        $cart = $request->getSession()->getSessionCart($this->eventDispatcher);

        foreach ($wishList->getWishListProducts() as $wishListProduct) {
            $event = new CartEvent($cart);
            $event
                ->setProduct($wishListProduct->getProductSaleElements()->getProductId())
                ->setProductSaleElementsId($wishListProduct->getProductSaleElementsId())
                ->setQuantity($wishListProduct->getQuantity())
                ->setAppend(true)
                ->setNewness(true)
            ;

            $this->eventDispatcher->dispatch($event, TheliaEvents::CART_ADDITEM);
        }
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

    protected function getWishListObject($wishListId, $customerId, $sessionId): ?WishList
    {
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
                $sessionId = $request->getSession()->getId();
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
