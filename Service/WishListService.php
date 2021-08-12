<?php

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

use WishList\Model\WishListQuery;

class WishListService
{
    public function check($request, $params, $session): bool
    {
        if (null !== $userId = $request->getSession()->getCustomerUser() && isset($params['product_id'])) {
            $wishListAssociationExist = WishListQuery::getExistingObject($userId, $params['product_id']);

            if (null !== $wishListAssociationExist || (!empty($session) && \in_array($params['product_id'], $session))) {
                return true;
            }
        }
        return false;
    }
}
