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
use OpenApi\Model\Api\ModelFactory;
use OpenApi\Service\OpenApiService;
use Symfony\Component\Routing\Annotation\Route;

use Symfony\Component\HttpFoundation\Request;
use Thelia\Controller\Front\BaseFrontController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Thelia\Core\Security\SecurityContext;
use Thelia\Model\ProductQuery;
use TheliaSmarty\Template\Plugins\Security;
use WishList\Model\WishList;
use WishList\Model\WishListQuery;
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
     *     summary="Get a wishlist",
     *     @OA\Parameter(
     *          name="wishListId",
     *          in="query",
     *          @OA\Schema(
     *              type="integer"
     *          )
     *     ),
     *     @OA\Response(
     *          response="200",
     *          description="Success",
     *          @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                property="wishList",
     *                ref="#/components/schemas/WishList"
     *             )
     *          )
     *     )
     * )
     */
    public function getWishList(WishListService $wishListService, Request $request, ModelFactory $modelFactory)
    {
        $wishListId = $request->get('wishListId');

        return OpenApiService::jsonResponse($this->getOpenApiWishList($wishListId, $modelFactory, $wishListService));
    }

    /**
     * @Route("/all", name="list_all", methods="GET")
     * @OA\Get(
     *     path="/wishlist/all",
     *     tags={"WishList"},
     *     summary="Get all wishlist of a customer",
     *     @OA\Response(
     *          response="200",
     *          description="Success",
     *          @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                  type="object",
     *                  @OA\Property(
     *                      property="wishList",
     *                      ref="#/components/schemas/WishList"
     *                  )
     *              )
     *          )
     *     )
     * )
     */
    public function getWishLists(WishListService $wishListService, ModelFactory $modelFactory)
    {
        $wishLists = $wishListService->getAllWishLists();

        $openApiWishLists = [];
        foreach ($wishLists as $wishList){
            $openApiWishLists[] = $this->getOpenApiWishList($wishList->getId(), $modelFactory, $wishListService);
        }

        return OpenApiService::jsonResponse($openApiWishLists);
    }

    /**
     * @Route("/create", name="create", methods="POST")
     * @OA\Post(
     *     path="/wishlist/create",
     *     tags={"WishList"},
     *     summary="Create a wishlist",
     *     @OA\RequestBody(
     *         @OA\MediaType(
     *              mediaType="application/json",
     *              @OA\Schema(
     *                  type="object",
     *                  @OA\Property(
     *                      property="title",
     *                      type="string"
     *                  ),
     *                  @OA\Property(
     *                      property="productSaleElements",
     *                      type="array",
     *                      @OA\Items(
     *                          type="object",
     *                          @OA\Property(
     *                              property="productSaleElementId",
     *                              type="integer"
     *                          ),
     *                          @OA\Property(
     *                              property="quantity",
     *                              type="integer"
     *                          )
     *                      )
     *                  )
     *              )
     *         )
     *     ),
     *     @OA\Response(
     *          response="200",
     *          description="Success",
     *          @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                property="wishList",
     *                ref="#/components/schemas/WishList"
     *             )
     *          )
     *     ),
     *     @OA\Response(
     *          response="400",
     *          description="Error"
     *     )
     * )
     */
    public function createWishList(Request $request, WishListService $wishListService, ModelFactory $modelFactory)
    {
        $data = json_decode($request->getContent(), true);

        $wishListTitle = array_key_exists('title', $data) ? $data['title'] : null;
        $wishListProducts = array_key_exists('productSaleElements', $data) ? $data['productSaleElements'] : null;
        $wishList = $wishListService->createUpdateWishList($wishListTitle, $wishListProducts);

        return OpenApiService::jsonResponse($this->getOpenApiWishList($wishList->getId(), $modelFactory, $wishListService));
    }

    /**
     * @Route("/duplicate/{wishListId}", name="duplicate", methods="POST")
     * @OA\Post(
     *     path="/wishlist/duplicate/{wishListId}",
     *     tags={"WishList"},
     *     summary="Duplicate a wishlist",
     *     @OA\Parameter(
     *         in="path",
     *         name="wishListId",
     *         @OA\Schema(
     *           type="integer"
     *         )
     *      ),
     *     @OA\RequestBody(
     *         @OA\MediaType(
     *              mediaType="application/json",
     *              @OA\Schema(
     *                  type="object",
     *                  @OA\Property(
     *                      property="title",
     *                      type="string"
     *                  )
     *              )
     *         )
     *     ),
     *     @OA\Response(
     *          response="200",
     *          description="Success",
     *          @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                property="wishList",
     *                ref="#/components/schemas/WishList"
     *             )
     *          )
     *     ),
     *     @OA\Response(
     *          response="400",
     *          description="Error"
     *     )
     * )
     */
    public function duplicateWishList($wishListId, Request $request, WishListService $wishListService, ModelFactory $modelFactory)
    {
        $data = json_decode($request->getContent(), true);

        $wishListTitle = array_key_exists('title', $data) ? $data['title'] : null;
        $wishList = $wishListService->duplicateWishList($wishListId, $wishListTitle);

        return OpenApiService::jsonResponse($this->getOpenApiWishList($wishList->getId(), $modelFactory, $wishListService));
    }

    /**
     * @Route("/update/{wishListId}", name="update", methods="POST")
     * @OA\Post(
     *     path="/wishlist/update/{wishListId}",
     *     tags={"WishList"},
     *     summary="Update a wishlist",
     *     @OA\Parameter(
     *         in="path",
     *         name="wishListId",
     *         @OA\Schema(
     *           type="integer"
     *         )
     *      ),
     *     @OA\RequestBody(
     *         @OA\MediaType(
     *              mediaType="application/json",
     *              @OA\Schema(
     *                  type="object",
     *                  @OA\Property(
     *                      property="title",
     *                      type="string"
     *                  )
     *              )
     *         )
     *     ),
     *     @OA\Response(
     *          response="200",
     *          description="Success",
     *          @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                property="wishList",
     *                ref="#/components/schemas/WishList"
     *             )
     *          )
     *     ),
     *     @OA\Response(
     *          response="400",
     *          description="Error"
     *     )
     * )
     */
    public function updateWishList($wishListId, Request $request, WishListService $wishListService, ModelFactory $modelFactory)
    {
        $data = json_decode($request->getContent(), true);

        $wishListTitle = array_key_exists('title', $data) ? $data['title'] : null;
        $wishList = $wishListService->createUpdateWishList($wishListTitle, null, $wishListId);

        return OpenApiService::jsonResponse($this->getOpenApiWishList($wishList->getId(), $modelFactory, $wishListService));
    }

    /**
     * @Route("/delete/{wishListId}", name="delete", methods="POST")
     * @OA\Post(
     *     path="/wishlist/delete/{wishListId}",
     *     tags={"WishList"},
     *     summary="delete a wishlist",
     *     @OA\Parameter(
     *         in="path",
     *         name="wishListId",
     *         @OA\Schema(
     *           type="integer"
     *         )
     *      ),
     *     @OA\Response(
     *          response="200",
     *          description="Success"
     *     ),
     *     @OA\Response(
     *          response="400",
     *          description="Error"
     *     )
     * )
     */
    public function deleteWishList($wishListId, Request $request, WishListService $wishListService, ModelFactory $modelFactory)
    {
        $wishListService->deleteWishList($wishListId);

        return new JsonResponse();
    }

    /**
     * @Route("/add/{productSaleElementId}/{wishListId}", name="add", methods="POST")
     * @OA\Post(
     *     path="/wishlist/add/{productSaleElementId}/{wishListId}",
     *     tags={"WishList"},
     *     summary="Add a product to wishlist",
     *     @OA\Parameter(
     *         in="path",
     *         name="productSaleElementId",
     *         @OA\Schema(
     *           type="integer"
     *         )
     *     ),
     *     @OA\Parameter(
     *         in="path",
     *         name="wishListId",
     *         @OA\Schema(
     *           type="integer"
     *         )
     *      ),
     *     @OA\RequestBody(
     *         @OA\MediaType(
     *              mediaType="application/json",
     *              @OA\Schema(
     *                  type="object",
     *                  @OA\Property(
     *                      property="quantity",
     *                      type="integer"
     *                  )
     *              )
     *         )
     *     ),
     *     @OA\Response(
     *          response="200",
     *          description="Success",
     *          @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                property="wishList",
     *                ref="#/components/schemas/WishList"
     *             )
     *          )
     *     ),
     *     @OA\Response(
     *          response="400",
     *          description="Error"
     *     )
     * )
     */
    public function addProduct($productSaleElementId, $wishListId, Request $request, WishListService $wishListService, ModelFactory $modelFactory)
    {
        $data = json_decode($request->getContent(), true);

        $quantity = array_key_exists('quantity', $data) ? $data['quantity'] : null;
        $wishListService->addProduct($productSaleElementId, $quantity, $wishListId);

        return OpenApiService::jsonResponse($this->getOpenApiWishList($wishListId, $modelFactory, $wishListService));
    }

    /**
     * @Route("/remove/{productSaleElementId}/{wishListId}", name="remove", methods="POST")
     * @OA\Post(
     *     path="/wishlist/remove/{productSaleElementId}/{wishListId}",
     *     tags={"WishList"},
     *     summary="Remove a product from wishlist",
     *     @OA\Parameter(
     *         in="path",
     *         name="productSaleElementId",
     *         @OA\Schema(
     *           type="integer"
     *         )
     *     ),
     *     @OA\Parameter(
     *         in="path",
     *         name="wishListId",
     *         @OA\Schema(
     *           type="integer"
     *         )
     *      ),
     *     @OA\Response(
     *          response="200",
     *          description="Success",
     *          @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                property="wishList",
     *                ref="#/components/schemas/WishList"
     *             )
     *          )
     *     ),
     *     @OA\Response(
     *          response="400",
     *          description="Error"
     *     )
     * )
     */
    public function removeProduct($productSaleElementId, $wishListId, WishListService $wishListService, ModelFactory $modelFactory)
    {
        $wishListService->removeProduct($productSaleElementId, $wishListId);

        return OpenApiService::jsonResponse($this->getOpenApiWishList($wishListId, $modelFactory, $wishListService));
    }

    /**
     * @Route("/clear/{wishListId}", name="clear", methods="POST")
     * @OA\Post(
     *     path="/wishlist/clear/{wishListId}",
     *     tags={"WishList"},
     *     summary="Clear the wishlist",
     *     @OA\Parameter(
     *         in="path",
     *         name="wishListId",
     *         @OA\Schema(
     *           type="integer"
     *         )
     *      ),
     *     @OA\Response(
     *          response="200",
     *          description="Success",
     *          @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                property="wishList",
     *                ref="#/components/schemas/WishList"
     *             )
     *          )
     *     )
     * )
     */
    public function clear($wishListId, WishListService $wishListService, ModelFactory $modelFactory)
    {
        $wishListService->clearWishList($wishListId);

        return OpenApiService::jsonResponse($this->getOpenApiWishList($wishListId, $modelFactory, $wishListService));
    }

    /**
     * @Route("/exist/{productSaleElementId}/{wishListId}", name="exist", methods="GET")
     * @OA\Get(
     *     path="/wishlist/exist/{productSaleElementId}/{wishListId}",
     *     tags={"WishList"},
     *     summary="Search if the product is in wishlist",
     *     @OA\Parameter(
     *         in="path",
     *         name="productSaleElementId",
     *         @OA\Schema(
     *           type="integer"
     *         )
     *     ),
     *     @OA\Parameter(
     *         in="path",
     *         name="wishListId",
     *         @OA\Schema(
     *           type="integer"
     *         )
     *      ),
     *     @OA\Response(
     *          response="200",
     *          description="Success",
     *          @OA\JsonContent(
     *                  type="boolean"
     *          )
     *     )
     * )
     */
    public function inWishList($productSaleElementId, $wishListId, WishListService $wishListService)
    {
        return new JsonResponse($wishListService->inWishList($productSaleElementId, $wishListId));
    }

    /**
     * @Route("/add-to-cart/{wishListId}", name="add_to_cart", methods="POST")
     * @OA\Post(
     *     path="/wishlist/add-to-cart/{wishListId}",
     *     tags={"WishList"},
     *     summary="Add wishlist to cart",
     *     @OA\Parameter(
     *         in="path",
     *         name="wishListId",
     *         @OA\Schema(
     *           type="integer"
     *         )
     *      ),
     *     @OA\Response(
     *          response="200",
     *          description="Success"
     *     )
     * )
     */
    public function addToCart($wishListId, WishListService $wishListService)
    {
        $wishListService->addWishListToCart($wishListId);

        return new JsonResponse();
    }

    protected function getOpenApiWishList($wishListId, ModelFactory $modelFactory, WishListService $wishListService)
    {
        $wishList = $wishListService->getWishList($wishListId);

        if (empty($wishList)){
            return null;
        }

        return $modelFactory->buildModel('WishList', $wishList);
    }
}