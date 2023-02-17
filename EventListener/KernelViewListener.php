<?php

namespace WishList\EventListener;

use Page\Model\PageQuery;
use Propel\Runtime\ActiveQuery\Criteria;
use Propel\Runtime\Exception\PropelException;
use SmartyException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Thelia\Core\Template\TemplateHelperInterface;
use Thelia\Model\ConfigQuery;
use Thelia\Tools\URL;
use TheliaSmarty\Template\SmartyParser;
use WishList\Model\WishListQuery;
use WishList\Service\WishListService;

class KernelViewListener implements EventSubscriberInterface
{
    protected RequestStack $requestStack;
    protected SmartyParser $parser;
    protected TemplateHelperInterface $templateHelper;
    protected WishListService $wishListService;

    public function __construct(
        RequestStack $requestStack,
        SmartyParser $parser,
        TemplateHelperInterface $templateHelper,
        WishListService $wishListService
    ) {
        $this->requestStack = $requestStack;
        $this->parser = $parser;
        $this->templateHelper = $templateHelper;
        $this->wishListService = $wishListService;
    }

    /**
     * @throws SmartyException
     * @throws PropelException
     */
    public function onKernelView(ViewEvent $event)
    {
        $this->parser->setTemplateDefinition($this->templateHelper->getActiveFrontTemplate(), true);

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
