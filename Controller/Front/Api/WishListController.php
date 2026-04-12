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
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Request;
use Thelia\Controller\Front\BaseFrontController;
use Thelia\Core\HttpFoundation\JsonResponse;
use WishList\Model\WishListQuery;
use WishList\Service\WishListService;

/**
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
    #[Route('/open_api/wishlist', name: 'api_wishlist_')]
    public function getWishList(WishListService $wishListService, Request $request, ModelFactory $modelFactory)
    {
        $wishListId = $request->get('wishListId');

        return OpenApiService::jsonResponse($this->getOpenApiWishList($wishListId, $modelFactory, $wishListService));
    }

    /**
     * @OA\Get(
     *     path="/wishlist/code/{code}",
     *     tags={"WishList"},
     *     summary="Get a wishlist by code",
     *     @OA\Parameter(
     *          name="code",
     *          in="path",
     *          @OA\Schema(
     *              type="string"
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
    #[Route('/code/{code}', name: 'get_by_code', methods: ['GET'])]
    public function getWishListByCode(string $code, ModelFactory $modelFactory)
    {
        $wishList = WishListQuery::create()->filterByCode($code)->findOne();

        if (null === $wishList) {
            return new JsonResponse([], Response::HTTP_NOT_FOUND);
        }

        return OpenApiService::jsonResponse($modelFactory->buildModel('WishList', $wishList));
    }

    /**
     * @OA\Get(
     *     path="/wishlist/lite/all",
     *     tags={"WishList"},
     *     summary="Get all wishlist of a customer without products",
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
    #[Route('/lite/all', name: 'list_all_without_products', methods: ['GET'])]
    public function getLiteWishLists(WishListService $wishListService, ModelFactory $modelFactory)
    {
        $wishLists = $wishListService->getAllWishLists();

        $openApiWishLists = [];
        foreach ($wishLists as $wishList) {
            $openApiWishLists[] = $this->getOpenApiWishList($wishList->getId(), $modelFactory, $wishListService, true);
        }

        return OpenApiService::jsonResponse($openApiWishLists);
    }

    /**
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
    #[Route('/all', name: 'list_all', methods: ['GET'])]
    public function getWishLists(WishListService $wishListService, ModelFactory $modelFactory)
    {
        $wishLists = $wishListService->getAllWishLists();

        $openApiWishLists = [];
        foreach ($wishLists as $wishList) {
            $openApiWishLists[] = $this->getOpenApiWishList($wishList->getId(), $modelFactory, $wishListService);
        }

        return OpenApiService::jsonResponse($openApiWishLists);
    }

    /**
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
    #[Route('/create', name: 'create', methods: ['POST'])]
    public function createWishList(Request $request, WishListService $wishListService, ModelFactory $modelFactory)
    {
        $data = json_decode($request->getContent(), true);

        $wishListTitle = array_key_exists('title', $data) ? $data['title'] : null;
        $wishListProducts = array_key_exists('productSaleElements', $data) ? $data['productSaleElements'] : null;
        $wishList = $wishListService->createUpdateWishList($wishListTitle, $wishListProducts);

        return OpenApiService::jsonResponse($this->getOpenApiWishList($wishList->getId(), $modelFactory, $wishListService));
    }

    /**
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
    #[Route('/duplicate/{wishListId}', name: 'duplicate', methods: ['POST'])]
    public function duplicateWishList($wishListId, Request $request, WishListService $wishListService, ModelFactory $modelFactory)
    {
        $data = json_decode($request->getContent(), true);

        $wishListTitle = array_key_exists('title', $data) ? $data['title'] : null;
        $wishList = $wishListService->duplicateWishList($wishListId, $wishListTitle);

        return OpenApiService::jsonResponse($this->getOpenApiWishList($wishList->getId(), $modelFactory, $wishListService));
    }

    /**
     * @OA\Post(
     *     path="/wishlist/duplicate_as_type/{wishListId}",
     *     tags={"WishList"},
     *     summary="Duplicate a wishlist as a wish list type",
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
    #[Route('/duplicate_as_type/{wishListId}', name: 'duplicate_as_type', methods: ['POST'])]
    public function duplicateWishListAsType($wishListId, Request $request, WishListService $wishListService, ModelFactory $modelFactory): JsonResponse
    {
        $wishList = WishListQuery::create()->findOneById($wishListId);

        if ($wishList === null) {
            return new JsonResponse('WishList not found', Response::HTTP_NOT_FOUND);
        }

        $isWishListTypeAlreadyExists = $wishListService->isWishListTypeAlreadyExists($wishList);
        if ($isWishListTypeAlreadyExists) {
            return new JsonResponse(
                'Wish List Type already exists',
                Response::HTTP_BAD_REQUEST,
            );
        }

        $wishListType = $wishListService->cloneWishList($wishListId);
        $wishListType
            ->setIsType(1)
            ->save();

        return OpenApiService::jsonResponse($this->getOpenApiWishList($wishListType->getId(), $modelFactory, $wishListService));
    }

    /**
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
    #[Route('/update/{wishListId}', name: 'update', methods: ['POST'])]
    public function updateWishList($wishListId, Request $request, WishListService $wishListService, ModelFactory $modelFactory)
    {
        $data = json_decode($request->getContent(), true);

        $wishListTitle = array_key_exists('title', $data) ? $data['title'] : null;
        $wishList = $wishListService->createUpdateWishList($wishListTitle, null, $wishListId);

        return OpenApiService::jsonResponse($this->getOpenApiWishList($wishList->getId(), $modelFactory, $wishListService));
    }

    /**
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
    #[Route('/delete/{wishListId}', name: 'delete', methods: ['POST'])]
    public function deleteWishList($wishListId, Request $request, WishListService $wishListService, ModelFactory $modelFactory)
    {
        $wishListService->deleteWishList($wishListId);

        return new JsonResponse();
    }

    /**
     * @OA\Post(
     *     path="/wishlist/add/{productSaleElementId}",
     *     tags={"WishList"},
     *     summary="Add a product to wishlist",
     *     @OA\Parameter(
     *         in="path",
     *         name="productSaleElementId",
     *         @OA\Schema(
     *           type="integer"
     *         )
     *     ),
     *     @OA\RequestBody(
     *         @OA\MediaType(
     *              mediaType="application/json",
     *              @OA\Schema(
     *                  type="object",
     *                  @OA\Property(
     *                      property="quantity",
     *                      type="integer"
     *                  ),
     *                  @OA\Property(
     *                      property="wishListId",
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
    #[Route('/add/{productSaleElementId}', name: 'add', methods: ['POST'])]
    public function addProduct($productSaleElementId, Request $request, WishListService $wishListService, ModelFactory $modelFactory)
    {
        $data = json_decode($request->getContent(), true);

        $wishListId = array_key_exists('wishListId', $data) ? $data['wishListId'] : null;
        $quantity = array_key_exists('quantity', $data) ? $data['quantity'] : null;


        $wishListService->addProduct($productSaleElementId, $quantity, $wishListId);

        return OpenApiService::jsonResponse($this->getOpenApiWishList($wishListId, $modelFactory, $wishListService));
    }
    /**
     * @OA\Post(
     *     path="/wishlist/set-default",
     *     tags={"WishList"},
     *     summary="Set a default wishlist",
     *     @OA\RequestBody(
     *         @OA\MediaType(
     *              mediaType="application/json",
     *              @OA\Schema(
     *                  type="object",
     *                  @OA\Property(
     *                      property="wishListId",
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
    #[Route('/set-default', name: 'set-default', methods: ['POST'])]
    public function setDefaultWishList(Request $request, WishListService $wishListService, ModelFactory $modelFactory)
    {
        $data = json_decode($request->getContent(), true);
        $wishListId = array_key_exists('wishListId', $data) ? $data['wishListId'] : null;

        $wishListService->setWishListToDefault($wishListId);

        return OpenApiService::jsonResponse($this->getOpenApiWishList($wishListId, $modelFactory, $wishListService));
    }

    /**
     * @OA\Post(
     *     path="/wishlist/remove/{productSaleElementId}",
     *     tags={"WishList"},
     *     summary="Remove a product from wishlist",
     *     @OA\Parameter(
     *         in="path",
     *         name="productSaleElementId",
     *         @OA\Schema(
     *           type="integer"
     *         )
     *     ),
     *     @OA\RequestBody(
     *         @OA\MediaType(
     *              mediaType="application/json",
     *              @OA\Schema(
     *                  type="object",
     *                  @OA\Property(
     *                      property="wishListId",
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
    #[Route('/remove/{productSaleElementId}', name: 'remove', methods: ['POST'])]
    public function removeProduct($productSaleElementId, WishListService $wishListService, ModelFactory $modelFactory, Request $request)
    {
        $data = json_decode($request->getContent(), true);

        $wishListId = array_key_exists('wishListId', $data) ? $data['wishListId'] : null;

        $wishListService->removeProduct($productSaleElementId, $wishListId);

        return OpenApiService::jsonResponse($this->getOpenApiWishList($wishListId, $modelFactory, $wishListService));
    }

    /**
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
    #[Route('/clear/{wishListId}', name: 'clear', methods: ['POST'])]
    public function clear($wishListId, WishListService $wishListService, ModelFactory $modelFactory)
    {
        $wishListService->clearWishList($wishListId);

        return OpenApiService::jsonResponse($this->getOpenApiWishList($wishListId, $modelFactory, $wishListService));
    }

    /**
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
    #[Route('/exist/{productSaleElementId}/{wishListId}', name: 'exist', methods: ['GET'])]
    public function inWishList($productSaleElementId, $wishListId, WishListService $wishListService)
    {
        return new JsonResponse($wishListService->inWishList($productSaleElementId, $wishListId));
    }

    /**
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
    #[Route('/add-to-cart/{wishListId}', name: 'add_to_cart', methods: ['POST'])]
    public function addToCart($wishListId, WishListService $wishListService)
    {
        $wishListService->addWishListToCart($wishListId);

        return new JsonResponse();
    }

    /**
     * @OA\Post(
     *     path="/wishlist/cart/from-wishlist/{wishListId}",
     *     tags={"WishList"},
     *     summary="Create cart from wishlist",
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
    #[Route('/cart/from-wishlist/{wishListId}', name: 'cart_from_wishlist', methods: ['POST'])]
    public function createCartFromWishlist($wishListId, WishListService $wishListService): JsonResponse
    {
        $wishListService->createCartFromWishlist($wishListId);

        return new JsonResponse();
    }

    protected function getOpenApiWishList($wishListId, ModelFactory $modelFactory, WishListService $wishListService, $lite = false)
    {
        $wishList = $wishListService->getWishList($wishListId);

        if (empty($wishList)) {
            return null;
        }

        if ($lite) {
            return $modelFactory->buildModel('WishListLite', $wishList);
        }

        return $modelFactory->buildModel('WishList', $wishList);
    }
}
