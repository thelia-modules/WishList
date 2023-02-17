<?php
/*************************************************************************************/
/*                                                                                   */
/*      Thelia	                                                                     */
/*                                                                                   */
/*      Copyright (c) OpenStudio                                                     */
/*	    email : info@thelia.net                                                      */
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

namespace WishList\Smarty\Plugins;

use Symfony\Component\HttpFoundation\RequestStack;
use TheliaSmarty\Template\AbstractSmartyPlugin;
use TheliaSmarty\Template\SmartyPluginDescriptor;
use WishList\Service\WishListService;

class WishList extends AbstractSmartyPlugin
{
    protected $wishListService = null;

    public function __construct(RequestStack $requestStack, WishListService $wishListService)
    {
        $this->wishListService = $wishListService;
    }

    /**
     * Check if product is in wishlist
     * @param $params
     */
    public function inWishList($params) : bool
    {
        $wishListId = array_key_exists('wish_list_id', $params) ? $params['wish_list_id'] : null;
        return $this->wishListService->inWishList($params['product_id'], $wishListId);
    }

    /**
     * @return array of SmartyPluginDescriptor
     */
    public function getPluginDescriptors()
    {
        return [
            new SmartyPluginDescriptor("function", "in_wishlist", $this, "inWishList"),
        ];
    }
}
