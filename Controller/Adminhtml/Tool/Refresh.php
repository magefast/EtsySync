<?php
/**
 * @author magefast@gmail.com www.magefast.com
 */

namespace Strekoza\EtsySync\Controller\Adminhtml\Tool;

use breakpoint\etsy\ProductSyncedData;
use Exception;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\View\Result\PageFactory;
use Strekoza\EtsySync\Service\Etsy;

class Refresh extends Action
{
    protected $resultPageFactory;

    /**
     * @var Etsy
     */
    private $etsy;

    /**
     * @var ProductSyncedData
     */
    private $productSyncedData;

    /**
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param Etsy $etsy
     * @param ProductSyncedData $productSyncedData
     */
    public function __construct(
        Context           $context,
        PageFactory       $resultPageFactory,
        Etsy              $etsy,
        ProductSyncedData $productSyncedData
    )
    {
        $this->resultPageFactory = $resultPageFactory;
        parent::__construct($context);

        $this->etsy = $etsy;
        $this->productSyncedData = $productSyncedData;
    }

    /**
     * @return ResponseInterface|Redirect|ResultInterface
     */
    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();

        try {

            $this->productSyncedData->refresh();

        } catch (Exception $e) {
            $this->messageManager->addError($e->getMessage());
        }

        return $resultRedirect->setPath('*/*/');
    }
}