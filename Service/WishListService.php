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

use Symfony\Component\HttpFoundation\RequestStack;
use Thelia\Core\Security\SecurityContext;
use Thelia\Log\Tlog;
use WishList\Model\WishListQuery;
use WishList\WishList;

class WishListService
{
    protected $securityContext = null;
    protected $requestStack = null;

    public function __construct(RequestStack $requestStack, SecurityContext $securityContext)
    {
        $this->securityContext = $securityContext;
        $this->requestStack = $requestStack;
    }

    public function addProduct($productId)
    {
        try {
            $customer = $this->securityContext->getCustomerUser();
            if (!$customer) {
                $sessionWishList = array_unique(array_merge($this->getSessionWishList(), [$productId]));
                $this->setSessionWishList($sessionWishList);
                return true;
            }

            $customerId = $customer->getId();

            $productWishList = WishListQuery::create()
                ->filterByProductId($productId)
                ->filterByCustomerId($customerId)
                ->findOneOrCreate();

            $productWishList->save();
        } catch (\Exception $e) {
            Tlog::getInstance()->error("Error during wishlist add :".$e->getMessage());
            return false;
        }

        return true;
    }

    public function removeProduct($productId)
    {
        try {
            $customer = $this->securityContext->getCustomerUser();
            if (!$customer) {
                $sessionWishListFiltered = array_filter(
                    $this->getSessionWishList(),
                    function ($element) use ($productId) {
                        return $element !== $productId;
                    }
                );

                $this->setSessionWishList($sessionWishListFiltered);

                return true;
            }

            $customerId = $customer->getId();

            $productWishList = WishListQuery::create()
                ->filterByProductId($productId)
                ->filterByCustomerId($customerId)
                ->findOne();

            if ($productWishList) {
                $productWishList->delete();
            }
        } catch (\Exception $e) {
            Tlog::getInstance()->error("Error during wishlist remove :".$e->getMessage());
            return false;
        }

        return true;
    }

    public function inWishList($productId): bool
    {
        $customer = $this->securityContext->getCustomerUser();
        if (!$customer) {
            return \in_array($productId, $this->getSessionWishList());
        }

        return null !== WishListQuery::getExistingObject($customer->getId(), $productId);
    }

    public function getWishList()
    {
        $customer = $this->securityContext->getCustomerUser();
        if (!$customer) {
            return $this->getSessionWishList();
        }

        return WishListQuery::create()->filterByCustomerId($customer->getId())->select('product_id')->find()->toArray();
    }

    public function clearWishList()
    {
        $customer = $this->securityContext->getCustomerUser();
        if (!$customer) {
            $this->requestStack->getCurrentRequest()->getSession()->remove(WishList::WISHLIST_SESSION_KEY);
        }

        WishListQuery::create()->filterByCustomerId($customer->getId())->find()->delete();
    }

    protected function getSessionWishList()
    {
        $wishListSession = $this->requestStack->getCurrentRequest()->getSession()->get(WishList::WISHLIST_SESSION_KEY);

        return is_array($wishListSession) ? $wishListSession : [];
    }

    protected function setSessionWishList($wishList)
    {
        $this->requestStack->getCurrentRequest()->getSession()->set(WishList::WISHLIST_SESSION_KEY, $wishList);
    }
}
