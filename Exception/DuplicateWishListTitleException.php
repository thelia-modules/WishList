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

namespace WishList\Exception;

/**
 * Raised when a wish list title is already used by another wish list of the same
 * owner, either a customer or an anonymous session.
 */
class DuplicateWishListTitleException extends \RuntimeException
{
}
