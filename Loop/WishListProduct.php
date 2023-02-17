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
use Thelia\Core\Template\Element\PropelSearchLoopInterface;
use Thelia\Core\Template\Loop\Argument\Argument;
use Thelia\Core\Template\Loop\Argument\ArgumentCollection;
use Thelia\Model\Lang;
use WishList\Model\WishListProductQuery;
use WishList\Model\WishListQuery;

/**
 *
 * WishListProduct loop
 *
 *
 * Class WishListProduct
 * @package WishListProduct\Loop
 */
class WishListProduct extends BaseLoop implements PropelSearchLoopInterface
{
    protected function getArgDefinitions()
    {
        return new ArgumentCollection(
            Argument::createIntListTypeArgument('id'),
            Argument::createIntListTypeArgument('wish_list_id')
        );
    }


    public function buildModelCriteria()
    {
        $customer = $this->securityContext->getCustomerUser();
        $customerId = null !== $customer ? $customer->getId() : null;
        $sessionId = null;
        if (!$customer) {
            $sessionId = $this->requestStack->getCurrentRequest()->getSession()->getId();
        }

        $wishListProducts = WishListProductQuery::create();

        if (null !== $id = $this->getId()) {
            $wishListProducts->filterById($id);
        }

        if (null !== $wishListId = $this->getWishListId()) {
            $wishListProducts->filterByWishListId($wishListId);
        }

        if (null !== $customerId) {
            $wishListProducts
                ->useWishListQuery()
                ->filterByCustomerId($customerId)
                ->endUse();
        }

        if (null !== $sessionId) {
            $wishListProducts
                ->useWishListQuery()
                ->filterBySessionId($sessionId)
                ->endUse();
        }

        return $wishListProducts;
    }

    /**
     * @param LoopResult $loopResult
     *
     * @return LoopResult
     */
    public function parseResults(LoopResult $loopResult)
    {
        /** @var \WishList\Model\WishListProduct $wishlistProduct */
        foreach ($loopResult->getResultDataCollection() as $wishlistProduct){

            $loopResultRow = new LoopResultRow($wishlistProduct);

            $loopResultRow
                ->set("ID", $wishlistProduct->getId())
                ->set("WISH_LIST_ID", $wishlistProduct->getWishListId())
                ->set("PRODUCT_SALE_ELEMENT_ID", $wishlistProduct->getProductSaleElementsId())
                ->set("PRODUCT_ID", $wishlistProduct->getProductSaleElements()->getProductId())
                ->set("QUANTITY", $wishlistProduct->getQuantity())
                ;

            $loopResult->addRow($loopResultRow);
        }

        return $loopResult;
    }
}
