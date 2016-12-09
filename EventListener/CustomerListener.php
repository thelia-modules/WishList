<?php

namespace WishList\EventListener;

use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Thelia\Core\Event\TheliaEvents;
use Thelia\Core\Security\SecurityContext;
use WishList\Controller\Front\WishListController;
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

    public function customerLogout(Event $event)
    {
        $this->requestStack->getCurrentRequest()->getSession()->set(WishListController::SESSION_NAME, []);
    }

    public function customerLogin(Event $event)
    {
        if ($this->securityContext->hasCustomerUser()) {
            $productIds = array_unique(
                array_merge(
                    is_array($this->requestStack->getCurrentRequest()->getSession()->get(WishListController::SESSION_NAME)) ?
                        $this->requestStack->getCurrentRequest()->getSession()->get(WishListController::SESSION_NAME) : [],
                    WishListQuery::create()->filterByCustomerId($this->securityContext->getCustomerUser()->getId())->select('product_id')->find()->toArray()
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

            $this->requestStack->getCurrentRequest()->getSession()->set(WishListController::SESSION_NAME, $productIds);
        }
    }

    public static function getSubscribedEvents()
    {
        return array(
            TheliaEvents::CUSTOMER_LOGOUT => array("customerLogout", 128),
            TheliaEvents::CUSTOMER_LOGIN => array("customerLogin", 64)
        );
    }
}
