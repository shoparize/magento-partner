<?php

namespace Shoparize\Partner\Setup;

use Magento\Catalog\Model\Product;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\UninstallInterface;

class Uninstall implements UninstallInterface
{

    private EavSetupFactory $eavSetupFactory;

    public function __construct(EavSetupFactory $eavSetupFactory)
    {
        $this->eavSetupFactory = $eavSetupFactory;
    }

    public function uninstall(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();

        $eavSetup = $this->eavSetupFactory->create(['setup' => $setup]);
        if(!$setup->getAttributeId(Product::ENTITY, 'size_unit')) {
            $eavSetup->removeAttribute(Product::ENTITY, 'size_unit');
        }
        if(!$setup->getAttributeId(Product::ENTITY, 'shipping_length')) {
            $eavSetup->removeAttribute(Product::ENTITY, 'shipping_length');
        }
        if(!$setup->getAttributeId(Product::ENTITY, 'shipping_width')) {
            $eavSetup->removeAttribute(Product::ENTITY, 'shipping_width');
        }
        if(!$setup->getAttributeId(Product::ENTITY, 'shipping_height')) {
            $eavSetup->removeAttribute(Product::ENTITY, 'shipping_height');
        }
        if(!$setup->getAttributeId(Product::ENTITY, 'brand')) {
            $eavSetup->removeAttribute(Product::ENTITY, 'brand');
        }
        if(!$setup->getAttributeId(Product::ENTITY, 'gtin')) {
            $eavSetup->removeAttribute(Product::ENTITY, 'gtin');
        }

        $setup->endSetup();
    }
}
