<?php

declare(strict_types=1);

namespace WishList\EventListener;

use Propel\Runtime\Exception\PropelException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Thelia\Model\ConfigQuery;
use Thelia\Tools\URL;
use WishList\Model\WishListQuery;
use WishList\Service\WishListService;

class KernelViewListener implements EventSubscriberInterface
{
    public function __construct(
        protected RequestStack $requestStack,
        protected WishListService $wishListService
    ) {
    }

    /**
     * @throws PropelException
     */
    public function onKernelView(ViewEvent $event): void
    {
        $request = $this->requestStack->getCurrentRequest();
        $view = $request->attributes->get('_view');
        $viewId = $request->attributes->get($view . '_id');

        if ($view !== 'wishList' || !$viewId) {
            return;
        }

        $wishList = WishListQuery::create()
            ->filterById($viewId)
            ->findOne();

        if (!$wishList) {
            return;
        }

        $this->wishListService->cloneWishList($wishList->getId());

        $event->setResponse(new RedirectResponse(URL::getInstance()->absoluteUrl(ConfigQuery::read('wish_list_import_redirect_url', ''))));
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::VIEW => [
                ['onKernelView', 3]
            ],
        ];
    }
}
