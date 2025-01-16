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
use WishList\Model\WishList;
use WishList\Model\WishListProductQuery;
use WishList\Model\WishListQuery;
use WishList\WishList as WishListModule;

class WishListService
{
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

    public function getWishList($wishListId)
    {
        $customer = $this->securityContext->getCustomerUser();
        $customerId = $customer?->getId();
        $sessionId = null;
        if (!$customer) {
            $sessionId = $this->requestStack->getCurrentRequest()?->getSession()->getId();
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

        return $this->createUpdateWishList('Default');
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

    public function createUpdateWishList($title, $products = null, $wishListId = null)
    {
        [$customerId, $sessionId] = $this->getCurrentUserOrSession();

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
            $currentLang = $this->requestStack->getCurrentRequest()?->getSession()->get('thelia.current.lang');
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

    public function duplicateWishList($wishListId, $title)
    {
        [$customerId, $sessionId] = $this->getCurrentUserOrSession();
        /** @var Lang $currentLang */
        $currentLang = $this->requestStack->getCurrentRequest()?->getSession()->get('thelia.current.lang');

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
            $this->requestStack->getCurrentRequest()?->getSession()->clearSessionCart($this->eventDispatcher);

            $this->addWishlistProductsToCart($wishList);
        }
    }

    private function addWishlistProductsToCart(WishList $wishList): void
    {
        $cart = $this->requestStack->getCurrentRequest()?->getSession()->getSessionCart($this->eventDispatcher);

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
        $currentLang = $this->requestStack->getCurrentRequest()?->getSession()->get('thelia.current.lang');

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
            $sessionId = $this->requestStack->getCurrentRequest()?->getSession()->getId();
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
