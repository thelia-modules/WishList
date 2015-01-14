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
namespace WishList\Controller\Front;

use Thelia\Controller\Front\BaseFrontController;
use WishList\Event\WishListEvents;
use WishList\Model\WishListQuery;

/**
 *
 * WishList management controller
 *
 * @author Michaël Espeche <mespeche@openstudio.fr>
 */

class WishListController extends BaseFrontController
{

    public function addProduct($productId)
    {
        if ($customer = $this->getSecurityContext()->getCustomerUser()) {
            $customerId = $customer->getId();

            if (null === WishListQuery::getExistingObject($customerId, $productId)) {
                $data = array('product_id' => $productId, 'user_id' => $customerId);

                $event = $this->createEventInstance($data);
                $this->dispatch(WishListEvents::WISHLIST_ADD_PRODUCT, $event);
            }
        }

        $this->generateRedirect($this->getRequest()->headers->get('referer'), 301);
    }

    public function removeProduct($productId)
    {
        if ($customer = $this->getSecurityContext()->getCustomerUser()) {
            $customerId = $customer->getId();

            if (null !== $wishList = WishListQuery::getExistingObject($customerId, $productId)) {

                $data = array('product_id' => $productId, 'user_id' => $customerId);

                $event = $this->createEventInstance($data);
                $event->setWishList($wishList->getId());

                $this->dispatch(WishListEvents::WISHLIST_REMOVE_PRODUCT, $event);
            }
        }

        $this->generateRedirect($this->getRequest()->headers->get('referer'), 301);
    }

    /**
     * @param $data
     * @return \WishList\Event\WishListEvents
     */
    private function createEventInstance($data)
    {

        $wishListEvent = new WishListEvents(
            $data['product_id'], $data['user_id']
        );

        return $wishListEvent;
    }

}
