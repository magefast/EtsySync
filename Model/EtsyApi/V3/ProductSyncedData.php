<?php
/**
 * @author magefast@gmail.com www.magefast.com
 */

namespace breakpoint\etsy;

use Exception;
use Magento\Backend\Model\Session;
use Magento\Catalog\Block\Product\AbstractProduct;
use Magento\Catalog\Block\Product\Context;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Strekoza\EtsySync\Service\Etsy;
use Strekoza\EtsySync\Service\GetAllCategories;

ini_set('memory_limit', '2048M');

class ProductSyncedData extends AbstractProduct
{

    private $session;
    /**
     * @var GetAllCategories
     */
    private $getAllCategories;
    /**
     * @var Etsy
     */
    private $etsy;
    /**
     * @var ProductCollectionFactory
     */
    private $productCollectionFactory;
    /**
     * @var Visibility
     */
    private $visibility;

    public function __construct(
        Context                  $context,
        Session                  $session,
        Etsy                     $etsy,
        GetAllCategories         $getAllCategories,
        ProductCollectionFactory $productCollectionFactory,
        Visibility               $visibility,
        array                    $data = []
    )
    {
        $this->session = $session;
        $this->etsy = $etsy;
        $this->getAllCategories = $getAllCategories;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->visibility = $visibility;

        parent::__construct(
            $context,
            $data
        );
    }

    /**
     * @return array
     */
    public function get(): ?array
    {
        $categoryProductsAndEtsyProducts = $this->session->getCategoryProductsAndEtsyProducts();

        if ($categoryProductsAndEtsyProducts === false || empty($categoryProductsAndEtsyProducts)) {
            return null;
        }

        return $categoryProductsAndEtsyProducts;
    }

    /**
     * @return array
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function getCategoryProductsAndEtsyProducts(): array
    {
        $array = [];
        $etsyProducts = $this->getEtsyProductsArray();
        $magentoCategoryProducts = $this->getMagentoCategoryProductsArray();

        foreach ($magentoCategoryProducts as $keyCategoryId => $magentoCategoryProduct) {
            $temp = $magentoCategoryProduct;

            foreach ($magentoCategoryProduct as $key => $value) {
                if (!in_array($value, $etsyProducts)) {
                    unset($temp[$key]);
                }
            }

            $array[$keyCategoryId] = [
                'category_id' => $keyCategoryId,
                'magento_product_count' => count($magentoCategoryProduct),
                'etsy_product_count' => count($temp)
            ];
        }

        return $array;
    }

    /**
     * @return array
     * @throws Exception
     */
    private function getEtsyProductsArray(): array
    {
        $array = [];
        $etsyAllProducts = $this->etsy->getEtsyAllProductsArraySku();

        foreach ($etsyAllProducts as $p) {
            $array[$p] = $p;
        }

        return $array;
    }

    /**
     * @return array
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    private function getMagentoCategoryProductsArray(): array
    {
        $array = [];
        $categories = $this->getAllCategories->execute();

        foreach ($categories as $category) {
            $productSku = $this->getProductsByCategory($category['id']);

            $array[$category['id']] = $productSku;
        }

        return $array;
    }

    /**
     * @param $categoryId
     * @return array
     */
    private function getProductsByCategory($categoryId): array
    {

        $array = [];
        $collection = $this->productCollectionFactory->create();
        $collection->setVisibility($this->visibility->getVisibleInCatalogIds());
        $collection->addCategoriesFilter(['eq' => [$categoryId]]);

        $collection = $this->_addProductAttributesAndPrices($collection)
            ->addAttributeToSelect(['sku'])
            ->addStoreFilter()
            ->addFinalPrice()
            ->addAttributeToSort('created_at', 'desc')
            ->setPageSize(99999)
            ->setCurPage(1);

        foreach ($collection as $p) {
            if ((int)$p->getPrice() > 50000) {
                continue;
            }

            $array[(string)$p->getSku()] = (string)$p->getSku();
        }

        return $array;
    }

    /**
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function refresh()
    {
        $categoryProductsAndEtsyProducts = $this->getCategoryProductsAndEtsyProducts();

        $this->session->setCategoryProductsAndEtsyProducts($categoryProductsAndEtsyProducts);
    }
}