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

namespace WishList;

use Propel\Runtime\Connection\ConnectionInterface;
use Symfony\Component\DependencyInjection\Loader\Configurator\ServicesConfigurator;
use Thelia\Install\Database;
use Thelia\Module\BaseModule;
use WishList\Model\WishListProduct;
use WishList\Model\WishListProductQuery;
use WishList\Model\WishListQuery;

class WishList extends BaseModule
{
    const WISHLIST_SESSION_KEY = "WishList";
    const DOMAIN_NAME = "wishlist";

    /**
     * YOU HAVE TO IMPLEMENT HERE ABSTRACT METHODD FROM BaseModule Class
     * Like install and destroy
     */

    public function postActivation(ConnectionInterface $con = null) : void
    {

        try {
            WishListQuery::create()->find();
            WishListProductQuery::create()->find();
        }catch (\Exception $e){
            $database = new Database($con);
            $database->insertSql(null,  [__DIR__.'/Config/TheliaMain.sql']);
        }
    }
    /**
     * Defines how services are loaded in your modules.
     */
    public static function configureServices(ServicesConfigurator $servicesConfigurator): void
    {
        $servicesConfigurator->load(self::getModuleCode().'\\', __DIR__)
            ->exclude([THELIA_MODULE_DIR.ucfirst(self::getModuleCode()).'/I18n/*'])
            ->autowire(true)
            ->autoconfigure(true);
    }
}
