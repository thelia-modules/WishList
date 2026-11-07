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

namespace WishList\Tests\Unit\Service;

use PHPUnit\Framework\TestCase;
use Thelia\Domain\SaleUnit\SaleUnitContext;
use Thelia\Domain\SaleUnit\Stock\SaleUnitAvailableStock;
use Thelia\Domain\SaleUnit\Stock\SaleUnitStockResolver;
use Thelia\Model\ProductSaleElements;
use Thelia\Model\SaleUnit;
use WishList\Service\CartableStock;

final class CartableStockTest extends TestCase
{
    public function testItReadsTheNationalCounterWithoutSaleUnitStock(): void
    {
        self::assertSame(5, (new CartableStock())->availableQuantity($this->productSaleElements(5)));
    }

    public function testItReadsTheNationalCounterWhenNoSaleUnitIsResolved(): void
    {
        self::assertSame(5, $this->cartableStock(null, [])->availableQuantity($this->productSaleElements(5)));
    }

    public function testASaleUnitStockCoversALineThatTheNationalCounterCannot(): void
    {
        self::assertSame(7, $this->cartableStock(3, [42 => 7.0])->availableQuantity($this->productSaleElements(0)));
    }

    public function testASaleUnitWithoutStockLeftRefusesALineThatTheNationalCounterCovers(): void
    {
        self::assertSame(0, $this->cartableStock(3, [42 => 0.0])->availableQuantity($this->productSaleElements(9)));
    }

    public function testASaleUnitWithoutOwnRowFallsBackOnTheNationalCounter(): void
    {
        self::assertSame(9, $this->cartableStock(3, [])->availableQuantity($this->productSaleElements(9)));
    }

    private function productSaleElements(int $nationalQuantity): ProductSaleElements
    {
        $productSaleElements = $this->createMock(ProductSaleElements::class);
        $productSaleElements->method('getId')->willReturn(42);
        $productSaleElements->method('getQuantity')->willReturn((float) $nationalQuantity);

        return $productSaleElements;
    }

    /**
     * @param array<int, float> $ownQuantities own quantity of the sale unit, keyed by sale element id
     */
    private function cartableStock(?int $saleUnitId, array $ownQuantities): CartableStock
    {
        $saleUnit = null;
        if (null !== $saleUnitId) {
            $saleUnit = $this->createMock(SaleUnit::class);
            $saleUnit->method('getId')->willReturn($saleUnitId);
        }

        $context = $this->createMock(SaleUnitContext::class);
        $context->method('getSaleUnit')->willReturn($saleUnit);

        $resolver = $this->createMock(SaleUnitStockResolver::class);
        $resolver->method('ownQuantity')->willReturnCallback(
            static fn (int $saleUnitId, int $productSaleElementsId): ?float => $ownQuantities[$productSaleElementsId] ?? null
        );

        return new CartableStock(new SaleUnitAvailableStock($context, $resolver));
    }
}
