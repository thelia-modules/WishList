<?php

namespace WishList\Model\Api;

use OpenApi\Annotations as OA;
use OpenApi\Model\Api\BaseApiModel;
use OpenApi\Model\Api\Product;
use Propel\Runtime\Exception\PropelException;


/**
 * Class WishList.
 *
 * @OA\Schema(
 *     description="WishList"
 * )
 */
class WishList extends BaseApiModel
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
    protected $customerId;

    /**
     * @var string
     * @OA\Property(
     *    type="string",
     * )
     */
    protected $sessionId;

    /**
     * @var string
     * @OA\Property(
     *    type="string",
     * )
     */
    protected $image;

    /**
     * @var string
     * @OA\Property(
     *    type="string",
     * )
     */
    protected $title;

    /**
     * @var string
     * @OA\Property(
     *    type="string",
     * )
     */
    protected $sharedUrl;

    /**
     * @var array
     * @OA\Property(
     *    type="array",
     *     @OA\Items(
     *          ref="#/components/schemas/WishListProduct"
     *     )
     * )
     * @Constraint\NotBlank(groups={"create", "update"})
     */
    protected array $products;

    /** @throws PropelException
     * @var \WishList\Model\WishList $theliaModel
     */
    public function createFromTheliaModel($theliaModel, $locale = null): void
    {
        parent::createFromTheliaModel($theliaModel, $locale);

       $this->id = $theliaModel->getId();

        $products = [];
        foreach ($theliaModel->getWishListProducts() as $wishListProduct) {
            $products[] = $this->modelFactory->buildModel('WishListProduct', $wishListProduct);
        }

        $this->setProducts($products);
        $this->setSharedUrl($theliaModel->getUrl($locale));
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
    public function setId($id): WishList
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return int
     */
    public function getCustomerId()
    {
        return $this->customerId;
    }

    /**
     * @param int $customerId
     * @return WishList
     */
    public function setCustomerId($customerId): WishList
    {
        $this->customerId = $customerId;
        return $this;
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @param string $title
     * @return WishList
     */
    public function setTitle($title): WishList
    {
        $this->title = $title;
        return $this;
    }

    /**
     * @return array
     */
    public function getProducts()
    {
        return $this->products;
    }

    /**
     * @param array $products
     * @return WishList
     */
    public function setProducts($products): WishList
    {
        $this->products = $products;
        return $this;
    }

    /**
     * @return string
     */
    public function getSharedUrl()
    {
        return $this->sharedUrl;
    }

    /**
     * @param string $sharedUrl
     * @return WishList
     */
    public function setSharedUrl($sharedUrl): WishList
    {
        $this->sharedUrl = $sharedUrl;
        return $this;
    }

    /**
     * @return string
     */
    public function getSessionId()
    {
        return $this->sessionId;
    }

    /**
     * @param string $sessionId
     * @return WishList
     */
    public function setSessionId($sessionId): WishList
    {
        $this->sessionId = $sessionId;
        return $this;
    }

    public function getImage()
    {
        return $this->image;
    }

    /**
     * @param $image
     */
    public function setImage($image): void
    {
        $this->image = $image;
    }
}
