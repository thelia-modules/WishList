<?php

declare(strict_types=1);

/*
 * This file is part of the Thelia package.
 * http://www.thelia.net
 *
 * (c) OpenStudio <info@thelia.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WishList\LiveComponent;

use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveArg;
use Symfony\UX\LiveComponent\Attribute\LiveListener;
use Symfony\UX\LiveComponent\ComponentToolsTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Thelia\Core\Security\SecurityContext;
use WishList\Service\WishListProductIndex;
use WishList\Service\WishListService;
use WishList\WishList as WishListModule;

/**
 * The wish lists of the signed-in customer, shown on the account page. Each product is a theme
 * product card, whose heart removes it; the section re-renders on the `wishlist:updated` event.
 */
#[AsLiveComponent(name: 'WishList:AccountWishLists', template: '@WishListModule/components/AccountWishLists.html.twig')]
final class AccountWishLists
{
    use DefaultActionTrait;
    use ComponentToolsTrait;

    public function __construct(
        private readonly WishListService $wishListService,
        private readonly WishListProductIndex $wishListProductIndex,
        private readonly SecurityContext $securityContext,
        private readonly TranslatorInterface $translator,
    ) {
    }

    /**
     * @return list<array{id: int, title: string, productIds: list<int>}>
     */
    public function getWishLists(): array
    {
        if (!$this->securityContext->hasCustomerUser()) {
            return [];
        }

        $productIds = $this->wishListService->getVisibleProductIdsByWishList();
        $wishLists = [];

        foreach ($this->wishListService->getAllWishLists() as $wishList) {
            $wishLists[] = [
                'id' => (int) $wishList->getId(),
                'title' => (string) $wishList->getTitle(),
                'productIds' => $productIds[$wishList->getId()] ?? [],
            ];
        }

        return $wishLists;
    }

    /**
     * @return array<string, string>
     */
    public function getLabels(): array
    {
        $labels = [];

        foreach ([
            'title' => 'My wishlists',
            'noList' => 'You have no wishlist yet. Use the heart on a product to start one.',
            'emptyList' => 'This wishlist is empty.',
            'delete' => 'Delete this wishlist',
        ] as $key => $message) {
            $labels[$key] = $this->translator->trans($message, [], WishListModule::DOMAIN_NAME);
        }

        return $labels;
    }

    #[LiveListener('wishlist:updated')]
    public function refresh(): void
    {
        // A heart of one of the cards changed a list: the re-render that follows reads it again.
        $this->wishListProductIndex->reset();
    }

    #[LiveAction]
    public function deleteWishList(#[LiveArg] int $wishListId): void
    {
        // Scoped to the lists of the current owner by the service.
        $this->wishListService->deleteWishList($wishListId);
        $this->wishListProductIndex->reset();
    }
}
