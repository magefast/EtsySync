<?php
/**
 * @author magefast@gmail.com www.magefast.com
 */

namespace Strekoza\EtsySync\Controller\Adminhtml\Sync;

use Exception;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\View\Result\PageFactory;
use Strekoza\EtsySync\Service\Etsy;
use Strekoza\EtsySync\Service\InventoryUpdate;

class Inventory extends Action
{
    protected $resultPageFactory;

    /**
     * @var Etsy
     */
    private $etsy;

    /**
     * @var InventoryUpdate
     */
    private $inventoryUpdate;

    /**
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param Etsy $etsy
     * @param InventoryUpdate $inventoryUpdate
     */
    public function __construct(
        Context         $context,
        PageFactory     $resultPageFactory,
        Etsy            $etsy,
        InventoryUpdate $inventoryUpdate
    )
    {
        $this->resultPageFactory = $resultPageFactory;
        parent::__construct($context);

        $this->etsy = $etsy;
        $this->inventoryUpdate = $inventoryUpdate;
    }

    /**
     * @return ResponseInterface|Redirect|ResultInterface
     */
    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();

        try {
            $this->inventoryUpdate->refresh();
            $this->messageManager->addNotice('Updated Price & Qty');
        } catch (Exception $e) {
            $this->messageManager->addError($e->getMessage());
        }

        return $resultRedirect->setPath('*/*/');
    }
}
