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

use Propel\Runtime\Exception\PropelException;
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
 * @method int|null getCustomerId()
 * @method int|null getId()
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
            Argument::createIntListTypeArgument('id'),
            Argument::createIntListTypeArgument('customer_id'),
            Argument::createAlphaNumStringTypeArgument('code'),
            Argument::createBooleanTypeArgument('default'),
        );
    }

    /**
     * Return array of search results
     * @return WishListQuery
     */
    public function buildModelCriteria()
    {
        $sessionId = null;

        // In the back-office, we allow any customer.
        if (! $this->getBackendContext() || (null === $customerId = $this->getCustomerId())) {
            $customer = $this->securityContext->getCustomerUser();
            $customerId = $customer?->getId();

            if (! $customer) {
                $sessionId = $this->requestStack->getCurrentRequest()?->getSession()->getId();
            }
        }

        $wishList = WishListQuery::create();

        if (null !== $id = $this->getId()) {
            $wishList->filterById($id);
        }

        if (null !== $code = $this->getCode()) {
            $wishList->filterByCode($code);
        }

        if (null !== $customerId) {
            $wishList->filterByCustomerId($customerId);
        }

        if (null !== $sessionId) {
            $wishList->filterBySessionId($sessionId);
        }

        if (null !== $default = $this->getDefault()) {
            $wishList->filterByDefault($default);
        }

        return $wishList;
    }

    /**
     * @param LoopResult $loopResult
     *
     * @return LoopResult
     * @throws PropelException
     */
    public function parseResults(LoopResult $loopResult)
    {
        /** @var \WishList\Model\WishList $wishlist */
        foreach ($loopResult->getResultDataCollection() as $wishlist) {

            $loopResultRow = new LoopResultRow($wishlist);

            /** @var Lang $currentLang */
            $currentLocale = $this->requestStack->getCurrentRequest()?->getSession()->getLang()->getLocale();

            $loopResultRow
                ->set("ID", $wishlist->getId())
                ->set("DEFAULT", $wishlist->getDefault())
                ->set("TITLE", $wishlist->getTitle())
                ->set("CODE", $wishlist->getCode())
                ->set("CUSTOMER_ID", $wishlist->getCustomerId())
                ->set("SESSION_ID", $wishlist->getSessionId())
                ->set("CREATED_AT", $wishlist->getCreatedAt())
                ->set("UPDATED_AT", $wishlist->getUpdatedAt())
                ->set("SHARED_URL", $wishlist->getUrl($currentLocale))
            ;

            $loopResult->addRow($loopResultRow);
        }

        return $loopResult;
    }
}
