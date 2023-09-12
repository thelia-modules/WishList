<?php

namespace WishList\Model;

use WishList\Model\Base\WishListProductQuery as BaseWishListProductQuery;

/**
 * Skeleton subclass for performing query and update operations on the 'wish_list_product' table.
 *
 *
 *
 * You should add additional methods to this class to meet the
 * application requirements.  This class will only be generated as
 * long as it does not already exist in the output directory.
 */
class WishListProductQuery extends BaseWishListProductQuery
{
    /**
     * Load an existing object from the database
     */
    public static function getExistingObject($wishListId, $customerId, $sessionId, $pseId)
    {
        $query =  self::create()
            ->filterByProductSaleElementsId($pseId);

        if (null !== $wishListId) {
            $query->useWishListQuery()
                ->filterById($wishListId)
                ->endUse();
        }

        if (null !== $customerId) {
            $query
                ->useWishListQuery()
                ->filterByCustomerId($customerId)
                ->endUse();
        }

        if (null !== $sessionId) {
            $query
                ->useWishListQuery()
                ->filterBySessionId($sessionId)
                ->endUse();
        }

        $q = $query->findOne();
        return $q;
    }
}
