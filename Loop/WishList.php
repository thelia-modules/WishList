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

namespace WishList\Loop;

use Propel\Runtime\ActiveQuery\Criteria;
use Thelia\Core\Template\Element\ArraySearchLoopInterface;
use Thelia\Core\Template\Element\BaseLoop;
use Thelia\Core\Template\Element\LoopResult;
use Thelia\Core\Template\Element\LoopResultRow;
use Thelia\Core\Template\Loop\Argument\ArgumentCollection;
use WishList\Controller\Front\WishListController;
use WishList\Model\Base\WishListQuery;

/**
 *
 * WishList loop
 *
 *
 * Class WishList
 * @package WishList\Loop
 * @author Michaël Espeche <mespeche@openstudio.fr>
 */
class WishList extends BaseLoop implements ArraySearchLoopInterface
{
    protected $timestampable = false;

    /**
     * Definition of loop arguments
     *
     * example :
     *
     * public function getArgDefinitions()
     * {
     *  return new ArgumentCollection(
     *
     *       Argument::createIntListTypeArgument('id'),
     *           new Argument(
     *           'ref',
     *           new TypeCollection(
     *               new Type\AlphaNumStringListType()
     *           )
     *       ),
     *       Argument::createIntListTypeArgument('category'),
     *       Argument::createBooleanTypeArgument('new'),
     *       ...
     *   );
     * }
     *
     * @return \Thelia\Core\Template\Loop\Argument\ArgumentCollection
     */
    protected function getArgDefinitions()
    {
        return new ArgumentCollection();
    }

    /**
     * Return array of search results
     * @return array|mixed|null
     */
    public function buildArray()
    {
        $search = null;

        if ($this->securityContext->hasCustomerUser()) {
            $customer = $this->securityContext->getCustomerUser();
            $customerId = $customer->getId();
        }

        if ($customerId != null) {
            $wishList = WishListQuery::create()->filterByCustomerId($customerId, Criteria::IN);

            $wishArray = array();
            foreach ($wishList as $data) {
                $wishArray[] = $data->getProductId();
            }

            if ($session = $this->request->getSession()->get(WishListController::SESSION_NAME)) {
                $search = array_unique(array_merge($wishArray, $session));
            }

        } else {
            $search = $this->request->getSession()->get(WishListController::SESSION_NAME);
        }

        return $search;
    }

    /**
     * @param LoopResult $loopResult
     *
     * @return LoopResult
     */
    public function parseResults(LoopResult $loopResult)
    {

        $productIds = array();

        foreach ($loopResult->getResultDataCollection() as $wishlist) {
            $productIds[] = $wishlist;
        }

        if (!empty($productIds)) {
            $productIdsList = implode(',', $productIds);

            $loopResultRow = new LoopResultRow($wishlist);

            $loopResultRow
                ->set("WISHLIST_PRODUCT_LIST", $productIdsList)
            ;

            $loopResult->addRow($loopResultRow);
        }

        return $loopResult;
    }
}
