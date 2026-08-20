<?php

declare(strict_types=1);

namespace WishList\LiveComponent;

use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveArg;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\ComponentToolsTrait;
use Symfony\UX\LiveComponent\ComponentWithFormTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Thelia\Controller\Front\BaseFrontController;
use Thelia\Core\Form\FormServiceInterface;
use WishList\Exception\DuplicateWishListTitleException;
use WishList\Form\CreateUpdateWishListForm;
use WishList\Service\WishListService;

#[AsLiveComponent(name: 'WishList:WishListButton', template: '@WishListModule/components/WishListButton.html.twig')]
class WishListButton extends BaseFrontController
{
    use ComponentWithFormTrait;
    use DefaultActionTrait;
    use ComponentToolsTrait;

    #[LiveProp]
    public int $pseId;

    #[LiveProp(writable: true)]
    public bool $isOpen = false;

    public function __construct(
        private readonly WishListService $wishListService,
        private readonly FormServiceInterface $formService,
    ) {
    }

    protected function instantiateForm(): FormInterface
    {
        return $this->formService->getFormByName(CreateUpdateWishListForm::FORM_NAME);
    }

    public function isInWishList(): bool
    {
        return $this->wishListService->isPseInAnyWishList($this->pseId);
    }

    public function getWishLists(): iterable
    {
        return $this->wishListService->getAllWishLists();
    }

    public function getWishListsContaining(): iterable
    {
        return $this->wishListService->getWishListsContainingPse($this->pseId);
    }

    #[LiveAction]
    public function open(): void
    {
        $this->isOpen = true;
    }

    #[LiveAction]
    public function close(): void
    {
        $this->isOpen = false;
    }

    #[LiveAction]
    public function addToWishList(#[LiveArg] int $wishListId): void
    {
        $this->wishListService->addProduct($this->pseId, 1, $wishListId);
        $this->emit('wishlist:alert', [
                'message' => $this->translator->trans('Product added to your wishlist'),
            ]);
        $this->close();
    }

    #[LiveAction]
    public function removeFromWishList(#[LiveArg] int $wishListId): void
    {
        $this->wishListService->removeProduct($this->pseId, $wishListId);
        $this->emit('wishlist:alert', [
                'message' => $this->translator->trans('Product removed from your wishlist'),
            ]);
    }

    #[LiveAction]
    public function createAndAdd(): void
    {
        $this->submitForm();

        $form = $this->getForm();

        if (!$form->isValid()) {
            return;
        }

        $title = $form->getData()['title'] ?? null;

        try {
            $wishList = $this->wishListService->createUpdateWishList($title);
        } catch (DuplicateWishListTitleException $exception) {
            // Keep the modal open with the name still filled in so the customer can amend it.
            $form->get('title')->addError(new FormError($exception->getMessage()));

            return;
        }

        $this->wishListService->addProduct($this->pseId, 1, $wishList->getId());

        $this->emit('wishlist:alert', [
            'message' => $this->translator->trans('Product added to your wishlist'),
        ]);
        $this->close();

        $this->resetForm();
    }
}
