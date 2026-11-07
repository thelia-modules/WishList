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

namespace WishList\Service;

use Thelia\Domain\SaleUnit\Stock\SaleUnitAvailableStock;
use Thelia\Model\ProductSaleElements;

/**
 * The stock a visitor can put in the cart for a sale element: the one the cart judges a line
 * against. With a sale unit resolved (Thelia 3.3 and later) that is the unit's own stock when it
 * keeps one, the national counter otherwise; without a sale unit, or on a core that has none, the
 * national counter.
 */
class CartableStock
{
    public function __construct(private readonly ?SaleUnitAvailableStock $saleUnitAvailableStock = null)
    {
    }

    public function availableQuantity(ProductSaleElements $productSaleElements): int
    {
        if (null === $this->saleUnitAvailableStock) {
            return (int) $productSaleElements->getQuantity();
        }

        return (int) $this->saleUnitAvailableStock->availableQuantity($productSaleElements);
    }
}
