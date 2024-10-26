<?php
/**
 * @author magefast@gmail.com www.magefast.com
 */

namespace Strekoza\EtsySync\Block\Adminhtml\Sync;

use Magento\Backend\Block\Widget\Form\Container;
use Magento\Framework\DataObject;

class Sync extends Container
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
    protected $_controller = 'sync';

    /**
     * Instantiate save button
     *
     * @return void
     */
    protected function _construct()
    {
        DataObject::__construct();

        $this->buttonList->add(
            'inventory',
            [
                'label' => __('Sync Inventory'),
                'class' => 'primary',
                'onclick' => 'window.open(\'' . $this->getUrl('etsysync/sync/inventory') . '\',\'_self\')',
            ],
            1
        );

        $this->buttonList->add(
            'save',
            [
                'label' => __('Sync New Products'),
                'class' => 'primary',
                'onclick' => 'window.open(\'' . $this->getUrl('etsysync/tool') . '\',\'_self\')',
            ],
            1
        );
    }
}
