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

/**
 * A wish list line that did not reach the cart as requested, either because it was
 * skipped entirely or because its quantity was reduced to the available stock.
 */
final readonly class RejectedWishListProduct
{
    public function __construct(
        public int $productSaleElementsId,
        public ?int $productId,
        public ?string $productTitle,
        public int $requestedQuantity,
        public int $acceptedQuantity,
        public RejectionReason $reason,
    ) {
    }

    public function isSkipped(): bool
    {
        return 0 === $this->acceptedQuantity;
    }
}
