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

use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Thelia\Controller\Front\BaseFrontController;
use WishList\Service\WishListService;

/**
 * @Route("/wishlist", name="wishlist_")
 */
class WishListController extends BaseFrontController
{
    /**
     * @Route("/create", name="create", methods="POST")
     */
    public function createWishList(Request $request, WishListService $wishListService)
    {
        $wishListService->createUpdateWishList($request->get('title'), null);

        return $this->generateRedirect($request->getSession()->getReturnToUrl());
    }

    /**
     * @Route("/update/{wishListId}", name="update", methods="POST")
     */
    public function updateWishList($wishListId, Request $request, WishListService $wishListService)
    {
        $wishListService->createUpdateWishList($request->get('title'), null, $wishListId);

        return $this->generateRedirect($request->getSession()->getReturnToUrl());
    }

    /**
     * @Route("/delete/{wishListId}", name="delete", methods="POST")
     */
    public function deleteWishList($wishListId, Request $request, WishListService $wishListService)
    {
        $wishListService->deleteWishList($wishListId);

        return $this->generateRedirect($request->getSession()->getReturnToUrl());
    }

    /**
     * @Route("/add/{productId}/{wishListId}", name="add", methods="POST")
     */
    public function addProduct($productId, Request $request, WishListService $wishListService, $wishListId)
    {
        $wishListService->addProduct($productId, $request->get('quantity'), $wishListId);

        return $this->generateRedirect($request->getSession()->getReturnToUrl());
    }

    /**
     * @Route("/remove/{productId}/{wishListId}", name="remove", methods="POST")
     */
    public function removeProduct($productId, Request $request, WishListService $wishListService, $wishListId)
    {
        $wishListService->removeProduct($productId, $wishListId);

        return $this->generateRedirect($request->getSession()->getReturnToUrl());
    }

    /**
     * @Route("/clear/{wishListId}", name="clear", methods="POST")
     */
    public function clear(Request $request, WishListService $wishListService, $wishListId)
    {
        $wishListService->clearWishList($wishListId);

        return $this->generateRedirect($request->getSession()->getReturnToUrl());
    }
}
