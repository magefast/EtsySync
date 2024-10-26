<?php
/**
 * @author magefast@gmail.com www.magefast.com
 */

namespace Strekoza\EtsySync\Service;

use Exception;
use Magento\Catalog\Block\Product\AbstractProduct;
use Magento\Catalog\Block\Product\Context;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;

ini_set('memory_limit', '2048M');

class InventoryUpdate extends AbstractProduct
{
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

    /**
     * @param Context $context
     * @param Etsy $etsy
     * @param ProductCollectionFactory $productCollectionFactory
     * @param Visibility $visibility
     * @param array $data
     */
    public function __construct(
        Context                  $context,
        Etsy                     $etsy,
        ProductCollectionFactory $productCollectionFactory,
        Visibility               $visibility,
        array                    $data = []
    )
    {
        $this->etsy = $etsy;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->visibility = $visibility;

        parent::__construct(
            $context,
            $data
        );
    }

    /**
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function refresh()
    {
        $etsyProducts = $this->getEtsyProductsArray();
        $magentoProductsData = $this->getProductsCollection();

        $arrayUpdate = [];
        foreach ($etsyProducts as $etsyId => $sku) {
            if (isset($magentoProductsData[$sku])) {
                $arrayUpdate[$etsyId] = [
                    'etsy_id' => $etsyId,
                    'price' => $magentoProductsData[$sku]['price'],
                    'qty' => $magentoProductsData[$sku]['qty'],
                    'sku' => $sku,
                    'enabled' => !(($magentoProductsData[$sku]['qty'] === 0))
                ];
            }
        }

        $this->etsy->updateInventory($arrayUpdate);
    }

    /**
     * @return array
     * @throws Exception
     */
    private function getEtsyProductsArray(): array
    {
        return $this->etsy->getEtsyAllProductsArraySku();
    }

    /**
     * @param $categoryId
     * @return array
     * @throws LocalizedException
     */
    private function getProductsCollection(): array
    {
        $array = [];
        $collection = $this->productCollectionFactory->create();
        $collection->setVisibility($this->visibility->getVisibleInCatalogIds());

        $collection = $this->_addProductAttributesAndPrices($collection)
            ->addAttributeToSelect(['sku'])
            ->addStoreFilter()
            ->addFinalPrice()
            ->setPageSize(99999)
            ->setCurPage(1);

        $collection->joinField('qty',
            'cataloginventory_stock_item',
            'qty',
            'product_id=entity_id',
            null,
            'left'
        );

        foreach ($collection as $p) {
            if ((int)$p->getPrice() > 50000) {
                continue;
            }

            $qty = intval($p->getData('qty'));
            $array[(string)$p->getSku()] = ['qty' => $qty, 'price' => intval($p->getPrice())];
        }

        return $array;
    }
}