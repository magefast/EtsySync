<?php
/**
 * @author magefast@gmail.com www.magefast.com
 */

namespace Strekoza\EtsySync\Service;

use ErrorException;
use Etsy\EtsyRequestException;
use Etsy\EtsyResponseException;
use Exception;
use Magento\Catalog\Model\Config;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Catalog\Model\ProductFactory;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\CatalogInventory\Api\StockStateInterface;
use Strekoza\EtsySync\Service\Etsy\Api3;
use Strekoza\SEO\Service\GetDetailDescription;
use Strekoza\SEO\Service\GetProductImagesBySku;

ini_set('memory_limit', '2048M');

class Etsy
{
    public const BREAK = ' 
';

    /**
     * @var Visibility
     */
    private $catalogProductVisibility;

    /**
     * @var CollectionFactory
     */
    private $productCollectionFactory;

    /**
     * @var ProductFactory
     */
    private $productloader;

    /**
     * @var StockStateInterface
     */
    private $stockItem;

    /**
     * @var GetDetailDescription
     */
    private $getDetailDescriptionService;

    /**
     * @var GetProductImagesBySku
     */
    private $getProductImagesBySku;

    /**
     * @var Api3
     */
    private $api;

    /**
     * @var array
     */
    private $successResult = [];

    /**
     * @var array
     */
    private $noticeResult = [];

    /**
     * @var Config
     */
    private $catalogConfig;

    /**
     * @param CollectionFactory $productCollectionFactory
     * @param Visibility $catalogProductVisibility
     * @param ProductFactory $productLoader
     * @param StockStateInterface $stockItem
     * @param Api3 $api
     * @param GetDetailDescription $getDetailDescriptionService
     * @param GetProductImagesBySku $getProductImagesBySku
     * @param Config $catalogConfig
     * @param array $data
     */
    public function __construct(
        CollectionFactory     $productCollectionFactory,
        Visibility            $catalogProductVisibility,
        ProductFactory        $productLoader,
        StockStateInterface   $stockItem,
        Api3                  $api,
        GetDetailDescription  $getDetailDescriptionService,
        GetProductImagesBySku $getProductImagesBySku,
        Config                $catalogConfig,
        array                 $data = []
    )
    {
        $this->productCollectionFactory = $productCollectionFactory;
        $this->catalogProductVisibility = $catalogProductVisibility;
        $this->productloader = $productLoader;
        $this->stockItem = $stockItem;
        $this->api = $api;
        $this->getDetailDescriptionService = $getDetailDescriptionService;
        $this->getProductImagesBySku = $getProductImagesBySku;
        $this->catalogConfig = $catalogConfig;
    }

    /**
     * @return array
     */
    public function getResult(): array
    {
        return $this->successResult;
    }

    /**
     * @return array
     */
    public function getNotice(): array
    {
        return $this->noticeResult;
    }

    /**
     * @param int $categoryId
     * @param int $etsyTaxonomyId
     * @throws ErrorException
     */
    public function addByCategory(int $categoryId, int $etsyTaxonomyId)
    {
        try {
            $etsyAllProducts = $this->getEtsyAllProductsArraySku();
            $productsData = $this->getProductsData($categoryId, $etsyTaxonomyId);

            $i = 0;
            foreach ($productsData as $keySku => $p) {
                if (in_array($keySku, $etsyAllProducts)) {
                    continue;
                }

                if ((int)$p['price'] > 50000) {
                    $this->noticeResult[] = 'Product SKU: ' . (string)$keySku . ' not added to sync - price is too high.';
                    continue;
                }

                $this->addListing($p, $keySku);
                $i++;

                /**
                 * LIMIT FOR TEST
                 */
                if ($i > 200) {
                    return;
                }
            }

        } catch (EtsyResponseException | EtsyRequestException $e) {
            $debugInfo = $e->getDebugInfo();
            if (isset($debugInfo['info'])) {
                $debugInfo = $debugInfo['info'];
            } else {
                var_dump($categoryId);
                var_dump($debugInfo);
                die('2020202');
            }
            throw new ErrorException('API ERROR. ' . $debugInfo);
        } catch (Exception $e) {
            $message = $e->getMessage();
            throw new ErrorException('API ERROR. ' . $message);
        }
    }

    /**
     * @return array
     * @throws Exception
     */
    public function getEtsyAllProductsArraySku(): array
    {
        return $this->api->getAllProducts();
    }

    /**
     * @param int $categoryId
     * @param int $etsyTaxonomyId
     * @return array
     */
    private function getProductsData(int $categoryId, int $etsyTaxonomyId): array
    {
        $productsData = [];
        $collection = $this->productCollectionFactory->create();
        $collection->setVisibility($this->catalogProductVisibility->getVisibleInCatalogIds());
        $collection->addCategoriesFilter(['eq' => [$categoryId]]);
        $collection->addAttributeToSelect(['sku', 'name'])
            ->setVisibility($this->catalogProductVisibility->getVisibleInCatalogIds())
            ->addCategoriesFilter(['eq' => [$categoryId]])
            ->addStoreFilter()
            ->addFinalPrice()
            ->addAttributeToSort('created_at', 'desc')
            ->addMinimalPrice()
            ->addTaxPercents()
            ->addAttributeToSelect($this->catalogConfig->getProductAttributes())
            ->addUrlRewrite()
            ->setPageSize(99999)
            ->setCurPage(1);

        foreach ($collection as $p) {
            $product = $this->productloader->create()->load($p->getId());

            $description = $this->getFormattedDescription($product);

            $productsData[(string)$p->getSku()] = [
                'quantity' => (int)$this->stockItem->getStockQty($product->getId(), $product->getStore()->getWebsiteId()),
                'title' => trim($p->getName()),
                'description' => $description,
                'price' => floatval($product->getPrice()),
                'materials' => [],
                'shipping_profile_id' => Settings::SHIPPING_PROFILE_ID,
                'shipping_template_id' => Settings::SHIPPING_TEMPLATE_ID,
                'shop_section_id' => null,
                'image_ids' => [],
                'non_taxable' => false,
                //'image' => false,
                'state' => 'draft',
                'processing_min' => 1,
                'processing_max' => 3,
                'category_id' => $etsyTaxonomyId,
                'taxonomy_id' => $etsyTaxonomyId,
                'tags' => [],
                'who_made' => 'i_did',
                'is_supply' => false,
                'when_made' => Settings::WHEN_MADE_VALUE,
                'recipient' => null,
                'occasion' => '',
                'style' => [],
            ];

            unset($product);
        }
        unset($collection);

        return $productsData;
    }

    /**
     * @param $product
     * @return string
     */
    private function getFormattedDescription($product): string
    {
        $description = !empty($product->getDescription()) ? strip_tags($product->getDescription()) : '';
        $string = $description . self::BREAK . self::BREAK;
        $detailDescriptionArray = $this->getDetailDescriptionService->getInArray($product);

        if (!empty($detailDescriptionArray)) {

            foreach ($detailDescriptionArray as $key => $value) {
                $string .= $this->strong($key);

                foreach ($value as $keyValue => $valueValue) {
                    if ($keyValue == 'sku') {
                        continue;
                    }
                    $string .= $this->param($valueValue);
                }

                $string .= self::BREAK;
            }
        } else {
            $shortDescription = !empty($product->getShortDescription()) ? strip_tags($product->getShortDescription()) : '';
            $string .= $shortDescription . self::BREAK . self::BREAK;
        }

        return "$string";
    }

    /**
     * @param $string
     * @return string
     */
    private function strong($string): string
    {
        return strtoupper($string) . self::BREAK;
    }

    /**
     * @param $value
     * @return string
     */
    private function param($value): string
    {
        return $value['label'] . ': ' . $value['value'] . self::BREAK;
    }

    /**
     * @param array $data
     * @param $sku
     * @throws Exception
     */
    public function addListing(array $data, $sku)
    {
        $resultNewListingId = $this->api->addListing($data);

        $this->successResult[] = 'Added New Listing: ' . (string)$resultNewListingId;

        $this->api->addSkuToNewListing($resultNewListingId, $sku, $data['price']);

        $imagesData = $this->getImages($sku, $resultNewListingId);
        if (count($imagesData) !== 0) {
            foreach ($imagesData as $i) {
                $this->api->uploadListingImage($resultNewListingId, $i);
            }
        }
    }

    /**
     * @param $sku
     * @param $listingId
     * @return array
     */
    private function getImages($sku, $listingId): array
    {
        $images = [];

        $imagesPathArray = $this->getProductImagesBySku->getAllImages($sku);

        foreach ($imagesPathArray as $img) {
            if (file_exists($img['file'])) {
                $images[] = $img['file'];
            }
        }

        return $images;
    }

    /**
     * @param array $data
     * @throws Exception
     */
    public function updateInventory(array $data)
    {
        foreach ($data as $listing_id => $value) {
            $this->api->updateInventory($listing_id, $value);
        }
        $this->noticeResult[] = 'Updated Price & Qty';
    }
}
