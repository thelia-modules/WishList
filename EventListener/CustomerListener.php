<?php

namespace WishList\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Thelia\Core\Event\TheliaEvents;
use Thelia\Core\Security\SecurityContext;
use WishList\Model\WishListProductQuery;
use WishList\Service\WishListService;

/**
 * @author Gilles Bourgeat <gbourgeat@openstudio.fr>
 */
class CustomerListener implements EventSubscriberInterface
{
    /** @var RequestStack */
    protected $requestStack;

    /** @var SecurityContext */
    protected $securityContext;

    /** @var SecurityContext */
    protected $wishListService;

    public function __construct(RequestStack $requestStack, SecurityContext $securityContext, WishListService $wishListService)
    {
        $this->requestStack = $requestStack;
        $this->securityContext = $securityContext;
        $this->wishListService = $wishListService;
    }

    // On login merge session and DB wishlists then erase useless session wishlist
    public function customerLogin() : void
    {
        $sessionId = $this->requestStack->getCurrentRequest()->getSession()->getId();
        $this->wishListService->sessionToUser($sessionId);
    }

    public static function getSubscribedEvents()
    {
        return [
            TheliaEvents::CUSTOMER_LOGIN => ["customerLogin", 64]
        ];
    }
}
