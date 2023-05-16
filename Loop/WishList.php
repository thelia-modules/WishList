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
use WishList\Model\WishListQuery;

/**
 *
 * WishList loop
 *
 *
 * Class WishList
 * @package WishList\Loop
 * @author Michaël Espeche <mespeche@openstudio.fr>
 */
class WishList extends BaseLoop implements PropelSearchLoopInterface
{
    /**
     * Definition of loop arguments
     *
     * @return \Thelia\Core\Template\Loop\Argument\ArgumentCollection
     */
    protected function getArgDefinitions()
    {
        return new ArgumentCollection(
            Argument::createIntListTypeArgument('id')
        );
    }

    /**
     * Return array of search results
     * @return array|mixed|null
     */
    public function buildModelCriteria()
    {
        $customer = $this->securityContext->getCustomerUser();
        $customerId = null !== $customer ? $customer->getId() : null;
        $sessionId = null;
        if (!$customer) {
            $sessionId = $this->requestStack->getCurrentRequest()->getSession()->getId();
        }

        $wishList = WishListQuery::create();

        if (null !== $id = $this->getId()) {
            $wishList->filterById($id);
        }

        if (null !== $customerId) {
            $wishList->filterByCustomerId($customerId);
        }

        if (null !== $sessionId) {
            $wishList->filterBySessionId($sessionId);
        }

        return $wishList;
    }

    /**
     * @param LoopResult $loopResult
     *
     * @return LoopResult
     */
    public function parseResults(LoopResult $loopResult)
    {
        /** @var \WishList\Model\WishList $wishlist */
        foreach ($loopResult->getResultDataCollection() as $wishlist){

            $loopResultRow = new LoopResultRow($wishlist);

            /** @var Lang $currentLang */
            $currentLang = $this->requestStack->getCurrentRequest()->getSession()->get('thelia.current.lang');

            $loopResultRow
                ->set("ID", $wishlist->getId())
                ->set("TITLE", $wishlist->getTitle())
                ->set("CODE", $wishlist->getCode())
                ->set("CUSTOMER_ID", $wishlist->getCustomerId())
                ->set("SESSION_ID", $wishlist->getSessionId())
                ->set("CREATED_AT", $wishlist->getCreatedAt())
                ->set("UPDATED_AT", $wishlist->getUpdatedAt())
                ->set("SHARED_URL", $wishlist->getUrl($currentLang->getLocale()))
                ;

            $loopResult->addRow($loopResultRow);
        }

        return $loopResult;
    }
}
