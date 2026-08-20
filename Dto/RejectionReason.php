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

namespace WishList\Dto;

enum RejectionReason: string
{
    case ProductSaleElementMissing = 'product_sale_element_missing';
    case ProductNotVisible = 'product_not_visible';
    case OutOfStock = 'out_of_stock';
    case StockLimited = 'stock_limited';
}
