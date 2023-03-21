<?php

namespace WishList\Model\Api;

use OpenApi\Annotations as OA;
use OpenApi\Model\Api\BaseApiModel;
use OpenApi\Model\Api\ModelTrait\translatable;
use OpenApi\Model\Api\ProductSaleElement;
use Thelia\Model\ProductSaleElements;


/**
 * Class WishListPse.
 *
 * @OA\Schema(
 *     description="WishListPse"
 * )
 */
class WishListPse extends ProductSaleElement
{
    use translatable;

    /**
     * @var int
     * @OA\Property(
     *    type="integer",
     * )
     * @Constraint\NotBlank(groups={"read", "update"})
     */
    protected $productId;


    /**
     * @return int
     */
    public function getProductId()
    {
        return $this->productId;
    }

    /**
     * @param int $productId
     * @return WishListPse
     */
    public function setProductId($productId): WishListPse
    {
        $this->productId = $productId;
        return $this;
    }

    public function createFromTheliaModel($theliaModel, $locale = null)
    {
        parent::createFromTheliaModel($theliaModel, $locale);

        $product = $theliaModel->getProduct()->setLocale($locale);

        $this->setTitle($product->getTitle());
        $this->setDescription($product->getDescription());
        $this->setPostscriptum($product->getPostscriptum());
        $this->setChapo($product->getChapo());
        $this->setMetaTitle($product->getMetaTitle());
        $this->setMetaDescription($product->getMetaDescription());
        $this->setMetaKeywords($product->getMetaKeywords());

        $images = $theliaModel->getProductSaleElementsProductImages();
        $this->setImages($images->getData());
    }
}