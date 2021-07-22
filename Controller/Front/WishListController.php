<?php
/*************************************************************************************/
/*                                                                                   */
/*      Thelia                                                                       */
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
/*      along with this program. If not, see <http://www.gnu.org/licenses/>.         */
/*                                                                                   */
/*************************************************************************************/
namespace WishList\Controller\Front;

use Psr\EventDispatcher\EventDispatcherInterface;
use Thelia\Controller\Front\BaseFrontController;
use WishList\Event\WishListEvents;
use WishList\Model\WishListQuery;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 *
 * WishList management controller
 *
 * @author Michaël Espeche <mespeche@openstudio.fr>
 */

class WishListController extends BaseFrontController
{

    const SESSION_NAME = 'WishList';

    /**
     * Add a product to wishlist
     * @param $productId
     */
    /**
     * @Route("/wishlist/add/{productId}/{json}", name="front.wishlist.add", methods="post")
     */
    public function addProduct($productId, $json, EventDispatcherInterface $eventdispatcher)
    {
        $status = 'NOTLOGGED';
        $session = $this->getSession()->get(self::SESSION_NAME);

        if ($session == null) {
            $session = array();
        }

        // Save product into session
        if (!in_array($productId, $session)) {
            $session[] = $productId;
        }

        // If a customer is logged in
        if ($customer = $this->getSecurityContext()->getCustomerUser()) {
            $customerId = $customer->getId();

            // Create array of product realy in wishlist
            $wish = WishListQuery::create()->findByCustomerId($customerId);
            $wishArray = [];
            foreach ($wish as $data) {
                $wishArray[] = $data->getProductId();
            }

            // If customer hasn't product in his wishlist
            if (null === WishListQuery::getExistingObject($customerId, $productId)) {
                $data = array('product_id' => $productId, 'user_id' => $customerId);

                // Add product to wishlist
                $event = $this->createEventInstance($data);
                $eventdispatcher->dispatch($event, WishListEvents::WISHLIST_ADD_PRODUCT);

                // Merge session & database wishlist
                $session = array_unique(array_merge($wishArray, $session));
                $status = 'ADD';
            } else {
                $status = 'DUPLICATE';
            }

        }

        $this->getSession()->set(self::SESSION_NAME, $session);

        if ($json == 1) {
            return new JsonResponse($status);
        }
        return $this->generateRedirect($this->getSession()->getReturnToUrl(), 302);

    }

    /**
     * Remove a product from wishlist
     * @param $productId
     * @return \Symfony\Component\HttpFoundation\Response
     */
    /**
     * @Route("/wishlist/remove/{productId}", name="front.wishlist.remove", methods="post")
     */
    public function removeProduct($productId, EventDispatcherInterface $eventdispatcher)
    {
        $session = $this->getSession()->get(self::SESSION_NAME);

        // If session isn't empty and product is in session
        if (!empty($session) && in_array($productId, $session)) {
            // Remove product from session
            $key = array_search($productId, $session);
            unset($session[$key]);

            // Set new session values
            $this->getSession()->set(self::SESSION_NAME, $session);
        }

        // If a customer is logged in
        if ($customer = $this->getSecurityContext()->getCustomerUser()) {
            $customerId = $customer->getId();

            // If customer has product in his wishlist
            if (null !== $wishList = WishListQuery::getExistingObject($customerId, $productId)) {

                $data = array('product_id' => $productId, 'user_id' => $customerId);

                // Remove product from wishlist
                $event = $this->createEventInstance($data);
                $event->setWishList($wishList->getId());

                $eventdispatcher->dispatch($event, WishListEvents::WISHLIST_REMOVE_PRODUCT);
            }
        }

        return $this->generateRedirect($this->getSession()->getReturnToUrl(), 302);

    }

    /**
     * Clear wishlist completely
     * @return \Symfony\Component\HttpFoundation\Response
     */
    /**
     * @Route("front.wishlist.clear", name="/wishlist/clear", methods="post")
     */
    public function clear(EventDispatcherInterface $eventdispatcher)
    {
        // Clear session of wishlist
        $this->getSession()->remove(self::SESSION_NAME);

        // If customer is logged in
        if ($customer = $this->getSecurityContext()->getCustomerUser()) {
            $customerId = $customer->getId();

            // If the customer has a wishlist
            if (null !== $wishList = WishListQuery::create()->findOneByCustomerId($customerId)) {
                $data = array('product_id' => null, 'user_id' => $customerId);

                // Clear his wishlist
                $event = $this->createEventInstance($data);
                $event->setUserId($customerId);

                $eventdispatcher->dispatch($event, WishListEvents::WISHLIST_ADD_PRODUCT);
            }
        }

        return $this->generateRedirect($this->getSession()->getReturnToUrl(), 302);
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
