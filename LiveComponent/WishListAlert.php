<?php

declare(strict_types=1);

namespace WishList\LiveComponent;

use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveArg;
use Symfony\UX\LiveComponent\Attribute\LiveListener;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Thelia\Controller\Front\BaseFrontController;

#[AsLiveComponent(name: 'WishList:WishListAlert', template: '@WishListModule/components/WishListAlert.html.twig')]
class WishListAlert extends BaseFrontController
{
    use DefaultActionTrait;

    public ?string $message = null;

    #[LiveListener('wishlist:alert')]
    public function addMessage(#[LiveArg] string $message): void
    {
        $this->message = $message;
    }

    #[LiveAction]
    public function closeToast(): void
    {
        $this->message = null;
    }
}
