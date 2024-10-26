<?php
/**
 * @author magefast@gmail.com www.magefast.com
 */

namespace Strekoza\EtsySync\Controller\Adminhtml\Tool;

use breakpoint\etsy\ProductSyncedData;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\Page;
use Magento\Framework\View\Result\PageFactory;

class Index extends Action
{
    /**
     * @var PageFactory
     */
    protected $resultPageFactory;

    /**
     * @var ProductSyncedData
     */
    private $productSyncedData;

    /**
     * Constructor
     *
     * @param Context $context
     * @param ProductSyncedData $productSyncedData
     */
    public function __construct(
        Context           $context,
        ProductSyncedData $productSyncedData
    )
    {
        parent::__construct($context);
        $this->productSyncedData = $productSyncedData;
    }

    /**
     * @return Page
     */
    public function execute()
    {
        $this->_view->loadLayout();
        $this->_view->getPage()->getConfig()->getTitle()->prepend(__('Synchronisation Tool > Add New Products'));

        $this->_view->renderLayout();
    }
}
