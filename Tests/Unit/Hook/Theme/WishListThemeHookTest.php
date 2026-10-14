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

namespace WishList\Tests\Unit\Hook\Theme;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RequestContext;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;
use Twig\Loader\ArrayLoader;
use WishList\Hook\Theme\WishListThemeHook;

final class WishListThemeHookTest extends TestCase
{
    public function testItAnswersTheThemePointsOfTheContract(): void
    {
        $hook = $this->hook();

        foreach (['product.details.bottom', 'product-card.bottom', 'layout.header.actions', 'account.bottom', 'layout.body.bottom'] as $hookName) {
            self::assertTrue($hook->supports($hookName), $hookName);
        }

        self::assertFalse($hook->supports('product.top'));
    }

    public function testTheProductCardMountsTheButtonOnTheProduct(): void
    {
        self::assertSame('button:42:WishListButton--card', $this->hook()->render('product-card.bottom', ['productId' => 42]));
    }

    public function testTheProductPageReadsTheIdOfTheProductResource(): void
    {
        $hook = $this->hook();

        self::assertSame('button:7:WishListButton--product', $hook->render('product.details.bottom', ['product' => ['id' => 7]]));
        self::assertSame('button:8:WishListButton--product', $hook->render('product.details.bottom', ['product' => (object) ['id' => 8]]));
    }

    public function testNoProductRendersNothing(): void
    {
        $hook = $this->hook();

        self::assertSame('', $hook->render('product-card.bottom', []));
        self::assertSame('', $hook->render('product-card.bottom', ['productId' => 'abc']));
        self::assertSame('', $hook->render('product.details.bottom', ['product' => null]));
    }

    public function testTheHeaderLinkPointsAtTheListsOfTheAccountPage(): void
    {
        self::assertSame('header:/account#wishlists:My wishlists', $this->hook()->render('layout.header.actions', []));
    }

    public function testAThemeWithoutAccountRouteGetsNoHeaderLink(): void
    {
        self::assertSame('', $this->hook(withAccountRoute: false)->render('layout.header.actions', []));
    }

    private function hook(bool $withAccountRoute = true): WishListThemeHook
    {
        $loader = new ArrayLoader([
            '@WishListModule/theme_hook/button.html.twig' => 'button:{{ productId }}:{{ class }}',
            '@WishListModule/theme_hook/header.html.twig' => 'header:{{ url }}:{{ label }}',
            '@WishListModule/theme_hook/account.html.twig' => 'account',
            '@WishListModule/theme_hook/alert.html.twig' => 'alert',
        ]);

        $urlGenerator = new class($withAccountRoute) implements UrlGeneratorInterface {
            public function __construct(private readonly bool $withAccountRoute)
            {
            }

            public function generate(string $name, array $parameters = [], int $referenceType = self::ABSOLUTE_PATH): string
            {
                if (!$this->withAccountRoute) {
                    throw new RouteNotFoundException($name);
                }

                return '/account';
            }

            public function setContext(RequestContext $context): void
            {
            }

            public function getContext(): RequestContext
            {
                return new RequestContext();
            }
        };

        $translator = new class implements TranslatorInterface {
            public function trans(string $id, array $parameters = [], ?string $domain = null, ?string $locale = null): string
            {
                return $id;
            }

            public function getLocale(): string
            {
                return 'en_US';
            }
        };

        return new WishListThemeHook(new Environment($loader), $urlGenerator, $translator);
    }
}
