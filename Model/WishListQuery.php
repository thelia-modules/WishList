<?php

namespace WishList\Model;

use WishList\Model\Base\WishListQuery as BaseWishListQuery;

/**
 * Skeleton subclass for performing query and update operations on the 'wish_list' table.
 *
 *
 *
 * You should add additional methods to this class to meet the
 * application requirements.  This class will only be generated as
 * long as it does not already exist in the output directory.
 *
 */
class WishListQuery extends BaseWishListQuery
{

    /**
     * Load an existing object from the database
     */
    public static function getExistingObject($customerId, $productId)
    {
        return self::create()
            ->filterByCustomerId($customerId)
            ->filterByProductId($productId)
            ->findOne();
    }

} // WishListQuery
