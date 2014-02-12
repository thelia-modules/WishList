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

use Thelia\Core\HttpFoundation\Request;
use Thelia\Core\Template\Smarty\AbstractSmartyPlugin;
use Thelia\Core\Template\Smarty\SmartyPluginDescriptor;
use WishList\Model\WishListQuery;

class WishList extends AbstractSmartyPlugin
{

    protected $request = null;
    protected $userId = null;

    public function __construct(Request $request)
    {

        $this->request = $request;

        if($session = $this->request->getSession()->getCustomerUser()){
            $this->userId = $session->getId();
        }

    }

    public function inWishList($params){

        $ret = false;

        if (isset($params['product_id'])) {

            $wishListAssociationExist = WishListQuery::getExistingObject($this->userId, $params['product_id']);
            if (null !== $wishListAssociationExist) {
                $ret = true;
            }
        }

        return $ret;

    }


    /**
     * @return an array of SmartyPluginDescriptor
     */
    public function getPluginDescriptors()
    {
        return array(
            new SmartyPluginDescriptor("function", "in_wishlist", $this, "inWishList")
        );
    }
}
