<?php

namespace WishList\Model\Api;

use OpenApi\Annotations as OA;
use OpenApi\Model\Api\BaseApiModel;
use Propel\Runtime\Exception\PropelException;
use Thelia\Model\ProductImage;
use Thelia\Model\ProductSaleElements;

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

    /**
     * @param \WishList\Model\WishListProduct $theliaModel
     * @param $locale
     * @return WishListProduct
     * @throws PropelException
     */
    public function createFromTheliaModel($theliaModel, $locale = null): WishListProduct
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
     */
    public function setId($id)
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
