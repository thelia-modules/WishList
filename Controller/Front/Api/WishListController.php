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
namespace WishList\Controller\Front\Api;

use OpenApi\Annotations as OA;
use Symfony\Component\Routing\Annotation\Route;

use Symfony\Component\HttpFoundation\Request;
use Thelia\Controller\Front\BaseFrontController;
use Symfony\Component\HttpFoundation\JsonResponse;
use WishList\Service\WishListService;

/**
 * @Route("/open_api/wishlist", name="api_wishlist_")
 */
class WishListController extends BaseFrontController
{
    /**
     * @Route("", name="list", methods="GET")
     * @OA\Get(
     *     path="/wishlist",
     *     tags={"WishList"},
     *     summary="Get the current wishlist",
     *     @OA\Response(
     *          response="200",
     *          description="Success",
     *          @OA\JsonContent(
     *                  type="array",
     *                  @OA\Items(
     *                      type="number"
     *                  )
     *          )
     *     )
     * )
     */
    public function getWishList(WishListService $wishListService)
    {
        return new JsonResponse($wishListService->getWishList());
    }

    /**
     * @Route("/add/{productId}", name="add", methods="POST")
     * @OA\Post(
     *     path="/wishlist/add/{productId}",
     *     tags={"WishList"},
     *     summary="Add a product to wishlist",
     *     @OA\Parameter(
     *         in="path",
     *         name="productId",
     *         @OA\Schema(
     *           type="integer"
     *         )
     *     ),
     *     @OA\Response(
     *          response="200",
     *          description="Success",
     *          @OA\JsonContent(
     *                  type="array",
     *                  @OA\Items(
     *                      type="number"
     *                  )
     *          )
     *     ),
     *     @OA\Response(
     *          response="400",
     *          description="Error"
     *     )
     * )
     */
    public function addProduct($productId, Request $request, WishListService $wishListService)
    {
        $status = $wishListService->addProduct($productId);

        return new JsonResponse($wishListService->getWishList(), $status? 200:400);
    }

    /**
     * @Route("/remove/{productId}", name="remove", methods="POST")
     * @OA\Post(
     *     path="/wishlist/remove/{productId}",
     *     tags={"WishList"},
     *     summary="Remove a product from wishlist",
     *     @OA\Parameter(
     *         in="path",
     *         name="productId",
     *         @OA\Schema(
     *           type="integer"
     *         )
     *     ),
     *     @OA\Response(
     *          response="200",
     *          description="Success",
     *          @OA\JsonContent(
     *                  type="array",
     *                  @OA\Items(
     *                      type="number"
     *                  )
     *          )
     *     ),
     *     @OA\Response(
     *          response="400",
     *          description="Error"
     *     )
     * )
     */
    public function removeProduct($productId, Request $request, WishListService $wishListService)
    {
        $status = $wishListService->removeProduct($productId);
        return new JsonResponse($wishListService->getWishList(), $status? 200:400);
    }

    /**
     * @Route("/clear", name="clear", methods="POST")
     * @OA\Post(
     *     path="/wishlist/clear",
     *     tags={"WishList"},
     *     summary="Clear the wishlist",
     *     @OA\Response(
     *          response="200",
     *          description="Success"
     *     )
     * )
     */
    public function clear(Request $request, WishListService $wishListService)
    {
        $wishListService->clearWishList();
        return new JsonResponse();
    }

    /**
     * @Route("/exist/{productId}", name="exist", methods="GET")
     * @OA\Get(
     *     path="/wishlist/exist/{productId}",
     *     tags={"WishList"},
     *     summary="Search if the product is in wishlist",
     *     @OA\Parameter(
     *         in="path",
     *         name="productId",
     *         @OA\Schema(
     *           type="integer"
     *         )
     *     ),
     *     @OA\Response(
     *          response="200",
     *          description="Success",
     *          @OA\JsonContent(
     *                  type="boolean"
     *          )
     *     )
     * )
     */
    public function inWishList($productId, WishListService $wishListService)
    {
        return new JsonResponse($wishListService->inWishList($productId));
    }
}