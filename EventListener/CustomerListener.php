<?php

namespace WishList\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Thelia\Core\Event\TheliaEvents;
use Thelia\Core\Security\SecurityContext;
use WishList\Model\WishList;
use WishList\Model\WishListQuery;

/**
 * @author Gilles Bourgeat <gbourgeat@openstudio.fr>
 */
class CustomerListener implements EventSubscriberInterface
{
    /** @var RequestStack */
    protected $requestStack;

    /** @var SecurityContext */
    protected $securityContext;

    public function __construct(RequestStack $requestStack, SecurityContext $securityContext)
    {
        $this->requestStack = $requestStack;
        $this->securityContext = $securityContext;
    }

    public function customerLogout() : void
    {
        $this->requestStack->getCurrentRequest()->getSession()->remove(\WishList\WishList::WISHLIST_SESSION_KEY);
    }

    // On login merge session and DB wishlists then erase useless session wishlist
    public function customerLogin() : void
    {
        $customer = $this->securityContext->getCustomerUser();
        if ($customer) {
            $sessionWishList = $this->requestStack->getCurrentRequest()->getSession()->get(\WishList\WishList::WISHLIST_SESSION_KEY);
            $productIds = array_unique(
                array_merge(
                    is_array($sessionWishList)? $sessionWishList : [],
                    WishListQuery::create()->filterByCustomerId($customer->getId())->select('product_id')->find()->toArray()
                ), SORT_REGULAR
            );

            foreach ($productIds as $productId) {
                if (null === WishListQuery::create()
                        ->filterByCustomerId($this->securityContext->getCustomerUser()->getId())
                        ->filterByProductId($productId)
                        ->findOne()) {
                    (new WishList())
                        ->setCustomerId($this->securityContext->getCustomerUser()->getId())
                        ->setProductId($productId)
                        ->save();
                }
            }

            $this->requestStack->getCurrentRequest()->getSession()->remove(\WishList\WishList::WISHLIST_SESSION_KEY);
        }
    }

    public static function getSubscribedEvents()
    {
        return [
            TheliaEvents::CUSTOMER_LOGOUT => array("customerLogout", 128),
            TheliaEvents::CUSTOMER_LOGIN => array("customerLogin", 64)
        ];
    }
}
