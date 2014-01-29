<?php
/*************************************************************************************/
/*                                                                                   */
/*      Thelia	                                                                     */
/*                                                                                   */
/*      Copyright (c) OpenStudio                                                     */
/*      email : info@thelia.net                                                      */
/*      web : http://www.thelia.net                                                  */
/*                                                                                   */
/*      This program is free software; you can redistribute it and/or modify         */
/*      it under the terms of the GNU General Public License as published by         */
/*      the Free Software Foundation; either version 3 of the License                */
/*                                                                                   */
/*      This program is distributed in the hope that it will be useful,              */
/*      but WITHOUT ANY WARRANTY; without even the implied warranty of               */
/*      MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the                */
/*      GNU General Public License for more details.                                 */
/*                                                                                   */
/*      You should have received a copy of the GNU General Public License            */
/*	    along with this program. If not, see <http://www.gnu.org/licenses/>.         */
/*                                                                                   */
/*************************************************************************************/

namespace WishList\Event;

use Thelia\Core\Event\ActionEvent;

/**
 *
 * This class contains all WishList events identifiers used by WishList Core
 *
 * @author Michaël Espeche <mespeche@openstudio.fr>
 */

class WishListEvents extends ActionEvent
{

    const WISHLIST_ADD_PRODUCT = 'whishList.action.addProduct';

    const BEFORE_WISHLIST_ADD_PRODUCT   = 'whishList.action.beforeAddProduct';
    const AFTER_WISHLIST_ADD_PRODUCT    = 'whishList.action.afterAddProduct';

    const WISHLIST_DELETE_PRODUCT = 'whishList.action.deleteProduct';

    const BEFORE_WISHLIST_DELETE_PRODUCT   = 'whishList.action.beforeDeleteProduct';
    const AFTER_WISHLIST_DELETE_PRODUCT    = 'whishList.action.afterDeleteProduct';

    protected $userId;
    protected $productId;
    protected $wishList;

    function __construct($productId, $userId)
    {
        $this->productId = $productId;
        $this->userId = $userId;
    }

    /**
     * @param mixed $productId
     */
    public function setProductId($productId)
    {
        $this->productId = $productId;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getProductId()
    {
        return $this->productId;
    }

    /**
     * @param mixed $userId
     */
    public function setUserId($userId)
    {
        $this->userId = $userId;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * @param mixed $wishList
     */
    public function setWishList($wishList)
    {
        $this->wishList = $wishList;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getWishList()
    {
        return $this->wishList;
    }

    /**
     * check if wishList exists
     *
     * @return bool
     */
    public function hasWishList()
    {
        return null !== $this->wishList;
    }
}