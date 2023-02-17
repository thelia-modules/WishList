<?php

namespace WishList\Model;

use Propel\Runtime\Connection\ConnectionInterface;
use Thelia\Model\Tools\UrlRewritingTrait;
use WishList\Model\Base\WishList as BaseWishList;

/**
 * Skeleton subclass for representing a row from the 'wish_list' table.
 *
 *
 *
 * You should add additional methods to this class to meet the
 * application requirements.  This class will only be generated as
 * long as it does not already exist in the output directory.
 */
class WishList extends BaseWishList
{
    use UrlRewritingTrait;

    public function getRewrittenUrlViewName()
    {
        return 'wishList';
    }
}
