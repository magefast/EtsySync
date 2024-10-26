<?php
/**
 * @author magefast@gmail.com www.magefast.com
 */

namespace Strekoza\EtsySync\Block\Adminhtml\Sync;

use breakpoint\etsy\ProductSyncedData;
use Magento\Backend\Block\Widget\Context;
use Magento\Backend\Block\Widget\Form\Container;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Strekoza\EtsySync\Service\GetAllCategories;

class Tool extends Container
{
    /**
     * Block module name
     *
     * @var string|null
     */
    protected $_blockGroup = null;

    /**
     * Controller name
     *
     * @var string
     */
    protected $_controller = 'tool';

    /**
     * @var GetAllCategories
     */
    private $getAllCategoriesService;

    /**
     * @var ProductSyncedData
     */
    private $productSyncedData;

    /**
     * @return mixed
     */
    public function getCategories()
    {
        return $this->getAllCategoriesService->execute();
    }

    /**
     * @return array|null
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function getSyncedData(): ?array
    {
        $productSyncedData = $this->productSyncedData->get();
        if ($productSyncedData == null) {

            $this->productSyncedData->refresh();
            $productSyncedData = $this->productSyncedData->get();
        }

        return $productSyncedData;
    }

    /**
     * Instantiate save button
     *
     * @return void
     */
    protected function _construct()
    {
        DataObject::__construct();
        $this->buttonList->add(
            'save',
            [
                'label' => __('Refresh Sync Data'),
                'class' => 'primary',
                'onclick' => 'window.open(\'' . $this->getUrl('etsysync/tool/refresh') . '\',\'_self\')',
            ],
            1
        );
    }

    /**
     * Tool constructor.
     * @param Context $context
     * @param GetAllCategories $getAllCategoriesService
     * @param ProductSyncedData $productSyncedData
     */
    public function __construct(
        Context           $context,
        GetAllCategories  $getAllCategoriesService,
        ProductSyncedData $productSyncedData
    )
    {
        parent::__construct($context);

        $this->getAllCategoriesService = $getAllCategoriesService;
        $this->productSyncedData = $productSyncedData;
    }
}
