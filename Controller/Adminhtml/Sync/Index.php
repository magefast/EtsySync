<?php
/**
 * @author magefast@gmail.com www.magefast.com
 */

namespace Strekoza\EtsySync\Controller\Adminhtml\Sync;

use Magento\Backend\App\Action;
use Magento\Framework\View\Result\Page;

class Index extends Action
{
    /**
     * @return Page
     */
    public function execute()
    {
        $this->_view->loadLayout();
        $this->_view->getPage()->getConfig()->getTitle()->prepend(__('Etsy Products Synchronisation'));

        $this->_view->renderLayout();
    }
}
