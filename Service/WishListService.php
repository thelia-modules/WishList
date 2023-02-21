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
    protected $securityContext = null;
    protected $requestStack = null;
    protected $eventDispatcher = null;

    public function __construct(RequestStack $requestStack, SecurityContext $securityContext, EventDispatcherInterface $eventDispatcher)
    {
        $this->securityContext = $securityContext;
        $this->requestStack = $requestStack;
        $this->eventDispatcher = $eventDispatcher;
    }

    public function addProduct($pseId, $quantity, $wishListId)
    {
        try {
            $customer = $this->securityContext->getCustomerUser();
            $wishList = WishListQuery::create()->filterById($wishListId);
            if (!$customer) {
                $wishList->filterBySessionId($this->requestStack->getCurrentRequest()->getSession()->getId());
            } else {
                $wishList->filterByCustomerId($customer->getId());
            }

            if (null === $wishList->findOne()) {
                throw new \Exception(Translator::getInstance()->trans('There is no wishlist with this id for this customer', [], WishListModule::DOMAIN_NAME));
            }

            $productWishList = WishListProductQuery::create()
                ->filterByProductSaleElementsId($pseId)
                ->filterByWishListId($wishListId)
                ->findOneOrCreate();

            $productWishList
                ->setQuantity($quantity)
                ->save();

        } catch (\Exception $e) {
            Tlog::getInstance()->error("Error during wishlist add :".$e->getMessage());
            return false;
        }

        return true;
    }

    public function removeProduct($pseId, $wishListId)
    {
        try {
            $customer = $this->securityContext->getCustomerUser();
            $customerId = null !== $customer ? $customer->getId() : null;
            $sessionId = null;
            if (!$customer) {
                $sessionId = $this->requestStack->getCurrentRequest()->getSession()->getId();
            }

            $productWishList = WishListProductQuery::getExistingObject($wishListId, $customerId, $sessionId, $pseId);

            if ($productWishList) {
                $productWishList->delete();
            }
        } catch (\Exception $e) {
            Tlog::getInstance()->error("Error during wishlist remove :".$e->getMessage());
            return false;
        }

        return true;
    }

    public function inWishList($pseId, $wishListId): bool
    {
        $customer = $this->securityContext->getCustomerUser();
        $customerId = null !== $customer ? $customer->getId() : null;
        $sessionId = null;
        if (!$customer) {
            $sessionId = $this->requestStack->getCurrentRequest()->getSession()->getId();
        }

        return null !== WishListProductQuery::getExistingObject($wishListId, $customerId, $sessionId, $pseId);
    }

    public function getWishList($wishListId)
    {
        $customer = $this->securityContext->getCustomerUser();
        $customerId = null !== $customer ? $customer->getId() : null;
        $sessionId = null;
        if (!$customer) {
            $sessionId = $this->requestStack->getCurrentRequest()->getSession()->getId();
        }

        return $this->getWishListObject($wishListId, $customerId, $sessionId);
    }

    public function getAllWishLists()
    {
        $customer = $this->securityContext->getCustomerUser();
        $customerId = null !== $customer ? $customer->getId() : null;
        $sessionId = null;
        if (!$customer) {
            $sessionId = $this->requestStack->getCurrentRequest()->getSession()->getId();
        }

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
        $customer = $this->securityContext->getCustomerUser();
        $customerId = null !== $customer ? $customer->getId() : null;
        $sessionId = null;
        if (!$customer) {
            $sessionId = $this->requestStack->getCurrentRequest()->getSession()->getId();
        }

        $query =  WishListProductQuery::create()
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

    public function createUpdateWishList($title, $products, $wishListId = null)
    {
        $customer = $this->securityContext->getCustomerUser();
        $customerId = null !== $customer ? $customer->getId() : null;
        $sessionId = null;
        if (!$customer) {
            $sessionId = $this->requestStack->getCurrentRequest()->getSession()->getId();
        }

        $rewrittenUrl = null;
        if (null === $wishList = $this->getWishListObject($wishListId, $customerId, $sessionId)) {
            $wishList = new WishList();

            if (null !== $customerId) {
                $wishList->setCustomerId($customerId);
            }

            if (null !== $sessionId) {
                $wishList->setSessionId($sessionId);
            }

            $rewrittenUrl = bin2hex(random_bytes(20));
        }

        if (null !== $title) {
            $wishList->setTitle($title);
        }

        $wishList->save();

        if (null !== $rewrittenUrl) {
            $currentLang = $this->requestStack->getCurrentRequest()->getSession()->get('thelia.current.lang');
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

    public function deleteWishList($wishListId)
    {
        $customer = $this->securityContext->getCustomerUser();
        $customerId = null !== $customer ? $customer->getId() : null;
        $sessionId = null;
        if (!$customer) {
            $sessionId = $this->requestStack->getCurrentRequest()->getSession()->getId();
        }

        if (null !== $wishList = $this->getWishListObject($wishListId, $customerId, $sessionId)) {
            $wishList->delete();
        }
    }

    public function duplicateWishList($wishListId, $title)
    {
        $customer = $this->securityContext->getCustomerUser();
        $customerId = null !== $customer ? $customer->getId() : null;
        $sessionId = null;
        if (!$customer) {
            $sessionId = $this->requestStack->getCurrentRequest()->getSession()->getId();
        }
        /** @var Lang $currentLang */
        $currentLang = $this->requestStack->getCurrentRequest()->getSession()->get('thelia.current.lang');

        $wishList = $this->getWishListObject($wishListId, $customerId, $sessionId);

        $newWishList = (new WishList())
            ->setTitle($title)
            ->setCustomerId($customerId)
            ->setSessionId($sessionId);

        $newWishList->save();

        $newWishList
            ->setRewrittenUrl($currentLang->getLocale(), bin2hex(random_bytes(20)))
            ->save();

        foreach ($wishList->getWishListProducts() as $wishListProduct) {
            $this->addProduct($wishListProduct->getProductSaleElementsId(), $wishListProduct->getQuantity(), $newWishList->getId());
        }

        return $newWishList;
    }

    public function sessionToUser($sessionId)
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

    public function addWishListToCart($wishListId)
    {
        $customer = $this->securityContext->getCustomerUser();
        $customerId = null !== $customer ? $customer->getId() : null;
        $sessionId = null;
        if (!$customer) {
            $sessionId = $this->requestStack->getCurrentRequest()->getSession()->getId();
        }

        $wishList = $this->getWishListObject($wishListId, $customerId, $sessionId);

        if (null !== $wishList){
            $cart = $this->requestStack->getCurrentRequest()->getSession()->getSessionCart($this->eventDispatcher);

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
    }

    public function cloneWishList($wishListId)
    {
        $customer = $this->securityContext->getCustomerUser();
        $customerId = null !== $customer ? $customer->getId() : null;
        $sessionId = null;
        if (!$customer) {
            $sessionId = $this->requestStack->getCurrentRequest()->getSession()->getId();
        }

        $wishList = WishListQuery::create()->findPk($wishListId);

        /** @var Lang $currentLang */
        $currentLang = $this->requestStack->getCurrentRequest()->getSession()->get('thelia.current.lang');

        $newWishList = (new WishList())
            ->setTitle($wishList->getTitle())
            ->setCustomerId($customerId)
            ->setSessionId($sessionId);

        $newWishList->save();

        $newWishList
            ->setRewrittenUrl($currentLang->getLocale(), bin2hex(random_bytes(20)))
            ->save();

        foreach ($wishList->getWishListProducts() as $wishListProduct) {
            $this->addProduct($wishListProduct->getProductSaleElementsId(), $wishListProduct->getQuantity(), $newWishList->getId());
        }

        return $newWishList;
    }

    protected function getWishListObject($wishListId, $customerId, $sessionId)
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
}
