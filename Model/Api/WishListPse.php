<?php

namespace WishList\Model\Api;

use OpenApi\Annotations as OA;
use OpenApi\Model\Api\BaseApiModel;
use OpenApi\Model\Api\ModelTrait\translatable;
use OpenApi\Model\Api\ProductSaleElement;
use Propel\Runtime\Exception\PropelException;
use Thelia\Model\ProductImage;
use Thelia\Model\ProductSaleElements;
use Thelia\Model\ProductSaleElementsProductImage;


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

    /**
     * @param ProductSaleElements $theliaModel
     * @param null $locale
     * @throws PropelException
     */
    public function createFromTheliaModel($theliaModel, $locale = null)
    {
        parent::createFromTheliaModel($theliaModel, $locale);
        $product = $theliaModel->getProduct()->setLocale($locale);

        /** @var ProductSaleElementsProductImage $pseImage */
        $pseImage = $theliaModel->getProductSaleElementsProductImages()->getFirst()?->getProductImage();

        /** @var ProductImage $imageProduct */
        $imageProduct = $product->getProductImages()->getFirst();
        $image = $pseImage ?? $imageProduct;

        $this->setTitle($product->getTitle());
        $this->setDescription($product->getDescription());
        $this->setPostscriptum($product->getPostscriptum());
        $this->setChapo($product->getChapo());
        $this->setMetaTitle($product->getMetaTitle());
        $this->setMetaDescription($product->getMetaDescription());
        $this->setMetaKeywords($product->getMetaKeywords());
        $this->setImages([$this->modelFactory->buildModel('Image', $image)]);
    }
}
