<?php
/**
 * @author magefast@gmail.com www.magefast.com
 */

namespace Strekoza\EtsySync\Service;

use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;
use Magento\Framework\App\State;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\StoreManagerInterface;

ini_set('memory_limit', '2048M');

class GetAllCategories
{
    /**
     * @var array
     */
    private $productCatsAll = [];

    /**
     * @var CategoryCollectionFactory
     */
    private $categoryCollection;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @param State $state
     * @param StoreManagerInterface $storeManager
     * @param CategoryCollectionFactory $categoryCollection
     */
    public function __construct(
        State                     $state,
        StoreManagerInterface     $storeManager,
        CategoryCollectionFactory $categoryCollection
    )
    {
        //$state->setAreaCode('frontend');
        $this->storeManager = $storeManager;
        $this->categoryCollection = $categoryCollection;
    }

    /**
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function execute(): array
    {
        if (count($this->productCatsAll) > 0) {
            return $this->productCatsAll;
        }

        /**
         * All categories
         */
        $categories = array();
        $websiteId = $this->storeManager->getDefaultStoreView()->getWebsiteId();
        $storeId = $this->storeManager->getWebsite($websiteId)->getDefaultStore()->getId();
        $rootCatId = $this->storeManager->getStore($storeId)->getRootCategoryId();

        $categoriesAll = $this->categoryCollection->create()
            ->setStore($storeId)
            ->addFieldToFilter('is_active', array('eq' => '1'))
            ->addAttributeToFilter('path', array('like' => '1/' . $rootCatId . '/%'))
            ->addAttributeToSelect('path')
            ->addAttributeToSelect('etsy_taxonomy_id')
            ->addAttributeToSelect('name');

        foreach ($categoriesAll as $category) {
            $categories[(int)$category->getId()]['id'] = (int)$category->getId();
            $categories[(int)$category->getId()]['name'] = $category->getName();
            $categories[(int)$category->getId()]['path'] = $category->getPath();
            $categories[(int)$category->getId()]['etsy_taxonomy_id'] = $category->getData('etsy_taxonomy_id');
        }
        unset($categoriesAll);

        foreach ($categories as $categoryId => $categoryValue) {
            $path = explode('/', $categoryValue['path']);
            $string = array();
            $pathIds = array();

            foreach ($path as $pathId) {
                if ($pathId == $rootCatId || $pathId == 1) {
                    continue;
                }

                if (isset($categories[$pathId]) && isset($categories[$pathId]['name'])) {
                    $string[] = $categories[$pathId]['name'];

                    if ($categoryValue['name'] !== $categories[$pathId]['name']) {
                        $pathIds[] = (int)$pathId;
                    }
                }
            }

            $productCatsAll[$categoryId]['name'] = implode(' > ', $string);
            $productCatsAll[$categoryId]['ids'] = $pathIds;
            $productCatsAll[$categoryId]['id'] = $categoryId;
            $productCatsAll[$categoryId]['etsy_taxonomy_id'] = $categories[$pathId]['etsy_taxonomy_id'];
        }
        unset($categories);

        $this->productCatsAll = $productCatsAll;

        return $this->productCatsAll;
    }

}