<?php
/**
 * @author magefast@gmail.com www.magefast.com
 */

namespace Strekoza\EtsySync\Controller\Adminhtml\Tool;

use ErrorException;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use Strekoza\EtsySync\Service\Etsy;

class Add extends Action
{
    protected $resultPageFactory;

    /**
     * @var Etsy
     */
    private $etsy;

    /**
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param Etsy $etsy
     */
    public function __construct(
        Context     $context,
        PageFactory $resultPageFactory,
        Etsy        $etsy
    )
    {
        $this->resultPageFactory = $resultPageFactory;
        parent::__construct($context);

        $this->etsy = $etsy;
    }

    public function execute()
    {
        try {
            $resultRedirect = $this->resultRedirectFactory->create();
            $categoryId = $this->getRequest()->getParam('categoryId', null);
            $etsyTaxonomyId = $this->getRequest()->getParam('etsyTaxonomyId', null);

            if (empty($categoryId) || empty($etsyTaxonomyId)) {
                throw new ErrorException(
                    "Incorrect request. Params `categoryId` or `etsyTaxonomyId` - not exist"
                );
            }

            $this->etsy->addByCategory((int)$categoryId, (int)$etsyTaxonomyId);

            $result = $this->etsy->getResult();
            $resultNotice = $this->etsy->getNotice();

            foreach ($result as $r) {
                $this->messageManager->addSuccessMessage($r);
            }

            foreach ($resultNotice as $n) {
                $this->messageManager->addNoticeMessage($n);
            }

        } catch (ErrorException $e) {
            $this->messageManager->addError($e->getMessage());
        }

        return $resultRedirect->setPath('*/*/');
    }
}