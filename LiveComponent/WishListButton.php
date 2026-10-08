<?php

declare(strict_types=1);

namespace WishList\LiveComponent;

use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveArg;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\ComponentToolsTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Thelia\Controller\Front\BaseFrontController;
use WishList\Exception\DuplicateWishListTitleException;
use WishList\Service\WishListProductIndex;
use WishList\Service\WishListService;
use WishList\WishList as WishListModule;

/**
 * The heart that adds a product to a wish list.
 *
 * Mounted either with `pseId` (a given sale element, the historical use) or with `productId`
 * (a product card or a product page: the product as a whole). With `productId` the closed
 * button costs no query of its own, the shared WishListProductIndex answers for every card of
 * the page, and the default sale element is only looked up when the customer adds the product.
 *
 * The "new list" name is a plain writable prop rather than a Symfony form: a form would mint a
 * CSRF token, so write the session, on every card of every listing. Live actions are already
 * protected against cross-site calls by the live component request headers.
 */
#[AsLiveComponent(name: 'WishList:WishListButton', template: '@WishListModule/components/WishListButton.html.twig')]
class WishListButton extends BaseFrontController
{
    use DefaultActionTrait;
    use ComponentToolsTrait;

    private const TITLE_MAX_LENGTH = 255;

    #[LiveProp]
    public ?int $pseId = null;

    #[LiveProp]
    public ?int $productId = null;

    #[LiveProp(writable: true)]
    public bool $isOpen = false;

    #[LiveProp(writable: true)]
    public string $newTitle = '';

    public ?string $titleError = null;

    public function __construct(
        private readonly WishListService $wishListService,
        private readonly WishListProductIndex $wishListProductIndex,
    ) {
    }

    public function isInWishList(): bool
    {
        if (null !== $this->productId) {
            return $this->wishListProductIndex->contains($this->productId);
        }

        return null !== $this->pseId && $this->wishListService->isPseInAnyWishList($this->pseId);
    }

    public function getWishLists(): iterable
    {
        return $this->wishListService->getAllWishLists();
    }

    public function getWishListsContaining(): iterable
    {
        if (null !== $this->productId) {
            return $this->wishListService->getWishListsContainingProduct($this->productId);
        }

        return null !== $this->pseId ? $this->wishListService->getWishListsContainingPse($this->pseId) : [];
    }

    /**
     * Module templates cannot rely on |trans (it reads the theme catalog): labels come from here.
     *
     * @return array<string, string>
     */
    public function getLabels(): array
    {
        $labels = [];

        foreach ([
            'add' => 'Add to wishlist',
            'manage' => 'Manage wishlists',
            'yourLists' => 'Your wishlists',
            'addToList' => 'Add to a wishlist',
            'remove' => 'Remove',
            'createList' => 'Create a new wishlist',
            'listName' => 'Wishlist name',
            'createAndAdd' => 'Create & add',
            'addToExisting' => 'Add to an existing wishlist',
            'close' => 'Close',
        ] as $key => $message) {
            $labels[$key] = $this->trans($message);
        }

        return $labels;
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
        $pseId = $this->resolvePseId();

        if (null === $pseId || false === $this->wishListService->addProduct($pseId, 1, $wishListId)) {
            return;
        }

        $this->notify('Product added to your wishlist');
        $this->close();
    }

    #[LiveAction]
    public function removeFromWishList(#[LiveArg] int $wishListId): void
    {
        if (null !== $this->productId) {
            $this->wishListService->removeProductFromWishList($this->productId, $wishListId);
        } elseif (null !== $this->pseId) {
            $this->wishListService->removeProduct($this->pseId, $wishListId);
        }

        $this->notify('Product removed from your wishlist');
    }

    #[LiveAction]
    public function createAndAdd(): void
    {
        $title = trim($this->newTitle);

        if ('' === $title || mb_strlen($title) > self::TITLE_MAX_LENGTH) {
            $this->titleError = $this->trans('Please enter a name of 1 to 255 characters');

            return;
        }

        $pseId = $this->resolvePseId();

        if (null === $pseId) {
            return;
        }

        try {
            $wishList = $this->wishListService->createUpdateWishList($title);
        } catch (DuplicateWishListTitleException $exception) {
            // Keep the modal open with the name still filled in so the customer can amend it.
            $this->titleError = $exception->getMessage();

            return;
        }

        $this->wishListService->addProduct($pseId, 1, $wishList->getId());

        $this->notify('Product added to your wishlist');
        $this->newTitle = '';
        $this->close();
    }

    private function resolvePseId(): ?int
    {
        if (null !== $this->pseId) {
            return $this->pseId;
        }

        return null !== $this->productId ? $this->wishListService->findDefaultProductSaleElementsId($this->productId) : null;
    }

    private function notify(string $message): void
    {
        // The index was loaded before the change: let the re-render read the new state.
        $this->wishListProductIndex->reset();

        $this->emit('wishlist:alert', ['message' => $this->trans($message)]);
        $this->emit('wishlist:updated');
    }

    private function trans(string $message): string
    {
        return $this->translator->trans($message, [], WishListModule::DOMAIN_NAME);
    }
}
