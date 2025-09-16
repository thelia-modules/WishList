<?php

namespace WishList\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;
use WishList\Service\WishListService;
use Thelia\Model\ProductQuery;

class WishListExtension extends AbstractExtension
{
    protected WishListService $wishListService;

    public function __construct(WishListService $wishListService)
    {
        $this->wishListService = $wishListService;
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('in_wishlist', [$this, 'inWishList']),
        ];
    }

    /**
     * Vérifie si un produit est présent dans la WishList.
     *
     * @param int|null $pseId       Identifiant du ProductSaleElement
     * @param int|null $wishListId  Identifiant de la WishList (facultatif)
     * @param int|null $productId   Identifiant du produit (facultatif pour retrouver le PSE par défaut)
     *
     * @return bool
     */
    public function inWishList(?int $pseId = null, ?int $wishListId = null, ?int $productId = null): bool
    {
        if (null === $pseId) {
            if (null === $productId) {
                return false;
            }

            $product = ProductQuery::create()->findPk($productId);
            if (null === $product) {
                return false;
            }

            $defaultPse = $product->getDefaultSaleElements();
            if (null === $defaultPse) {
                return false;
            }

            $pseId = $defaultPse->getId();
        }

        return $this->wishListService->inWishList($pseId, $wishListId);
    }
}
