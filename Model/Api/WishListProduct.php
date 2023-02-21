<?php

namespace WishList\Model\Api;

use OpenApi\Annotations as OA;
use OpenApi\Model\Api\BaseApiModel;


/**
 * Class WishListProduct.
 *
 * @OA\Schema(
 *     description="WishListProduct"
 * )
 */
class WishListProduct extends BaseApiModel
{
    /**
     * @var int
     * @OA\Property(
     *    type="integer",
     * )
     * @Constraint\NotBlank(groups={"read", "update"})
     */
    protected $id;

    /**
     * @var integer
     * @OA\Property(
     *    type="number",
     *    format="integer",
     * )
     * @Constraint\NotBlank(groups={"create", "update"})
     */
    protected $wishListId;

    /**
     * @var int
     * @OA\Property(
     *    type="integer",
     * )
     */
    protected $quantity;

    /**
     * @var BaseApiModel
     * @OA\Property(
     *    type="object",
     *    ref="#/components/schemas/WishListPse"
     * )
     * @Constraint\NotBlank(groups={"create", "update"})
     */
    protected $productSaleElement;

    public function createFromTheliaModel($theliaModel, $locale = null)
    {
        $wishListProduct = parent::createFromTheliaModel($theliaModel, $locale);

        $wishListProduct->setProductSaleElement($this->modelFactory->buildModel('WishListPse', $theliaModel->getProductSaleElements()));

        return $wishListProduct;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     * @return WishList
     */
    public function setId($id): WishListProduct
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return int
     */
    public function getWishListId()
    {
        return $this->wishListId;
    }

    /**
     * @param int $wishlistId
     * @return WishListProduct
     */
    public function setWishListId($wishListId): WishListProduct
    {
        $this->wishListId = $wishListId;
        return $this;
    }

    /**
     * @return int
     */
    public function getQuantity()
    {
        return $this->quantity;
    }

    /**
     * @param int $quantity
     * @return WishListProduct
     */
    public function setQuantity($quantity): WishListProduct
    {
        $this->quantity = $quantity;
        return $this;
    }

    /**
     * @return BaseApiModel
     */
    public function getProductSaleElement()
    {
        return $this->productSaleElement;
    }

    /**
     * @param BaseApiModel $productSaleElement
     * @return WishListProduct
     */
    public function setProductSaleElement($productSaleElement): WishListProduct
    {
        $this->productSaleElement = $productSaleElement;
        return $this;
    }

}
