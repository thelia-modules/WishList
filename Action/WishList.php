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

namespace WishList\Action;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use WishList\Event\WishListEvents;
use WishList\Model\Base\WishListQuery;

/**
 *
 * WishList class where all actions are managed
 *
 * Class WishList
 * @package WishList\Action
 * @author Michaël Espeche <mespeche@openstudio.fr>
 */
class WishList implements EventSubscriberInterface
{

    /**
     * Add a product to wishlist
     * @param  WishListEvents                            $event
     * @throws \Exception
     * @throws \Propel\Runtime\Exception\PropelException
     */
    public function addProduct(WishListEvents $event)
    {
        $addProductToWishList = new \WishList\Model\WishList();

        $addProductToWishList
            ->setProductId($event->getProductId())
            ->setCustomerId($event->getUserId())
            ->save();
    }

    /**
     * Remove product from wishlist
     * @param  WishListEvents                            $event
     * @throws \Exception
     * @throws \Propel\Runtime\Exception\PropelException
     */
    public function removeProduct(WishListEvents $event)
    {
        if (null !== $wishList = WishListQuery::create()->findPk($event->getWishList())) {

            $wishList->delete();

            $event->setWishList($wishList);
        }

    }

    /**
     * Clear wishlist completly
     * @param  WishListEvents                            $event
     * @throws \Exception
     * @throws \Propel\Runtime\Exception\PropelException
     */
    public function clear(WishListEvents $event)
    {
        if (null !== $wishList = WishListQuery::create()->findOneByCustomerId($event->getUserId())) {
            WishListQuery::create()->filterByCustomerId($event->getUserId())->delete();
        }
    }

    /**
     * Returns an array of event names this subscriber wants to listen to.
     *
     * The array keys are event names and the value can be:
     *
     *  * The method name to call (priority defaults to 0)
     *  * An array composed of the method name to call and the priority
     *  * An array of arrays composed of the method names to call and respective
     *    priorities, or 0 if unset
     *
     * For instance:
     *
     *  * array('eventName' => 'methodName')
     *  * array('eventName' => array('methodName', $priority))
     *  * array('eventName' => array(array('methodName1', $priority), array('methodName2'))
     *
     * @return array The event names to listen to
     *
     * @api
     */
    public static function getSubscribedEvents()
    {
        return array(
            WishListEvents::WISHLIST_ADD_PRODUCT => array('addProduct', 128),
            WishListEvents::WISHLIST_REMOVE_PRODUCT => array('removeProduct', 128),
            WishListEvents::WISHLIST_CLEAR => array('clear', 128)
        );
    }
}
