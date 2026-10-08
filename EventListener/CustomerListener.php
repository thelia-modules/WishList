<?php

namespace WishList\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Thelia\Core\Event\Customer\CustomerLoginEvent;
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

    /**
     * Hands the lists a visitor built before signing in over to the customer.
     *
     * Runs before the core login listener (priority 128): that one stores the customer in the
     * session and renews the session id, after which the lists of the visitor could no longer
     * be found by their session id.
     */
    public function customerLogin(CustomerLoginEvent $event): void
    {
        $request = $this->requestStack->getCurrentRequest();
        if (null === $request || !$request->hasSession()) {
            return;
        }

        $sessionId = $request->getSession()->getId();
        if ('' === $sessionId) {
            return;
        }

        $this->wishListService->moveSessionListsToCustomer($sessionId, (int) $event->getCustomer()->getId());
    }

    public static function getSubscribedEvents(): array
    {
        return [
            TheliaEvents::CUSTOMER_LOGIN => ["customerLogin", 256]
        ];
    }
}
