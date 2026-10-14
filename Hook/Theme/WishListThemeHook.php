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

namespace WishList\Hook\Theme;

use Symfony\Component\Routing\Exception\ExceptionInterface as RoutingException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Thelia\Core\Hook\Theme\ThemeHookInterface;
use Twig\Environment;
use WishList\WishList as WishListModule;

/**
 * Puts the wish list in the theme:
 * - the heart on the product page (`product.details.bottom`) and on every product card
 *   (`product-card.bottom`): the WishList:WishListButton live component, mounted on the product;
 * - a link to the customer's lists in the header (`layout.header.actions`);
 * - the lists themselves on the account page (`account.bottom`);
 * - the toast that confirms an addition or a removal, once per page (`layout.body.bottom`).
 *
 * Nothing here queries the database or touches the session: the button reads whether the product
 * is already kept from WishListProductIndex, one query for the whole page.
 */
final readonly class WishListThemeHook implements ThemeHookInterface
{
    public const PRODUCT_HOOK = 'product.details.bottom';
    public const PRODUCT_CARD_HOOK = 'product-card.bottom';
    public const HEADER_HOOK = 'layout.header.actions';
    public const ACCOUNT_HOOK = 'account.bottom';
    public const PAGE_BOTTOM_HOOK = 'layout.body.bottom';

    public function __construct(
        private Environment $twig,
        private UrlGeneratorInterface $urlGenerator,
        private TranslatorInterface $translator,
    ) {
    }

    public function supports(string $hookName): bool
    {
        return \in_array($hookName, [
            self::PRODUCT_HOOK,
            self::PRODUCT_CARD_HOOK,
            self::HEADER_HOOK,
            self::ACCOUNT_HOOK,
            self::PAGE_BOTTOM_HOOK,
        ], true);
    }

    public function render(string $hookName, array $parameters): string
    {
        return match ($hookName) {
            self::PRODUCT_HOOK => $this->renderButton($this->productIdOf($parameters['product'] ?? null), 'WishListButton--product'),
            self::PRODUCT_CARD_HOOK => $this->renderButton(is_numeric($parameters['productId'] ?? null) ? (int) $parameters['productId'] : null, 'WishListButton--card'),
            self::HEADER_HOOK => $this->renderHeaderLink(),
            self::ACCOUNT_HOOK => $this->twig->render('@WishListModule/theme_hook/account.html.twig'),
            self::PAGE_BOTTOM_HOOK => $this->twig->render('@WishListModule/theme_hook/alert.html.twig'),
            default => '',
        };
    }

    private function renderButton(?int $productId, string $class): string
    {
        if (null === $productId || $productId <= 0) {
            return '';
        }

        return $this->twig->render('@WishListModule/theme_hook/button.html.twig', [
            'productId' => $productId,
            'class' => $class,
        ]);
    }

    private function renderHeaderLink(): string
    {
        try {
            $accountUrl = $this->urlGenerator->generate('account_index');
        } catch (RoutingException) {
            // A theme without the account route: no place to send the customer to.
            return '';
        }

        return $this->twig->render('@WishListModule/theme_hook/header.html.twig', [
            'url' => $accountUrl.'#wishlists',
            'label' => $this->translator->trans('My wishlists', [], WishListModule::DOMAIN_NAME),
        ]);
    }

    /**
     * The product page passes the product as read from the API: an array, or an object.
     */
    private function productIdOf(mixed $product): ?int
    {
        $productId = match (true) {
            \is_array($product) => $product['id'] ?? null,
            \is_object($product) && method_exists($product, 'getId') => $product->getId(),
            \is_object($product) => $product->id ?? null,
            default => null,
        };

        return is_numeric($productId) ? (int) $productId : null;
    }
}
