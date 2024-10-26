<?php

namespace Strekoza\EtsySync\Setup;

use Magento\Catalog\Model\Category;
use Magento\Catalog\Setup\CategorySetup;
use Magento\Catalog\Setup\CategorySetupFactory;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\UpgradeDataInterface;

class UpgradeData implements UpgradeDataInterface
{
    private $categorySetupFactory;
    private $eavSetupFactory;

    public function __construct(CategorySetupFactory $categorySetupFactory, EavSetupFactory $eavSetupFactory)
    {
        $this->categorySetupFactory = $categorySetupFactory;
        $this->eavSetupFactory = $eavSetupFactory;
    }

    public function upgrade(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        $installer = $setup;
        $installer->startSetup();
        if (version_compare($context->getVersion(), '1.1.2', '<=')) {
            // set new resource model paths
            /** @var CategorySetup $categorySetup */

            $categorySetup = $this->categorySetupFactory->create(['setup' => $setup]);
            $entityTypeId = $categorySetup->getEntityTypeId(Category::ENTITY);
            $attributeSetId = $categorySetup->getDefaultAttributeSetId($entityTypeId);

            $landingAttributes = [
                'etsy_taxonomy_id' => [
                    'type' => 'varchar',
                    'label' => 'Etsy Taxonomy ID',
                    'input' => 'text',
                    'required' => false,
                    'sort_order' => 400,
                    'global' => 1,
                    'used_in_product_listing' => false,
                    'group' => 'General',
                    'backend' => '',
                    'default' => null,
                    'user_defined' => false,
                    'visible' => true,
                    'source' => ''
                ]
            ];

            foreach ($landingAttributes as $item => $data) {
                $categorySetup->addAttribute(Category::ENTITY, $item, $data);
            }

            $idg = $categorySetup->getAttributeGroupId($entityTypeId, $attributeSetId, 'General');

            foreach ($landingAttributes as $item => $data) {
                $categorySetup->addAttributeToGroup(
                    $entityTypeId,
                    $attributeSetId,
                    $idg,
                    $item,
                    $data['sort_order']
                );
            }
        }

        $setup->endSetup();
    }
}
