<?php

namespace Shoparize\Partner\Setup;

use Magento\Catalog\Model\Product;
use Magento\Eav\Model\ResourceModel\Entity\Attribute;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\UninstallInterface;

class Uninstall implements UninstallInterface
{
    /**
     * @var EavSetupFactory
     */
    private EavSetupFactory $eavSetupFactory;

    /**
     * @var Attribute
     */
    private Attribute $eavAttribute;

    /**
     * @param EavSetupFactory $eavSetupFactory
     * @param Attribute $eavAttribute
     */
    public function __construct(
        EavSetupFactory $eavSetupFactory,
        Attribute $eavAttribute
    ) {
        $this->eavSetupFactory = $eavSetupFactory;
        $this->eavAttribute = $eavAttribute;
    }

    /**
     * Uninstall event
     *
     * @param SchemaSetupInterface $setup
     * @param ModuleContextInterface $context
     * @return void
     */
    public function uninstall(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();

        $eavSetup = $this->eavSetupFactory->create(['setup' => $setup]);
        if ($this->eavAttribute->getIdByCode(Product::ENTITY, 'size_unit')) {
            $eavSetup->removeAttribute(Product::ENTITY, 'size_unit');
        }
        if ($this->eavAttribute->getIdByCode(Product::ENTITY, 'shipping_length')) {
            $eavSetup->removeAttribute(Product::ENTITY, 'shipping_length');
        }
        if ($this->eavAttribute->getIdByCode(Product::ENTITY, 'shipping_width')) {
            $eavSetup->removeAttribute(Product::ENTITY, 'shipping_width');
        }
        if ($this->eavAttribute->getIdByCode(Product::ENTITY, 'shipping_height')) {
            $eavSetup->removeAttribute(Product::ENTITY, 'shipping_height');
        }
        if ($this->eavAttribute->getIdByCode(Product::ENTITY, 'brand')) {
            $eavSetup->removeAttribute(Product::ENTITY, 'brand');
        }
        if ($this->eavAttribute->getIdByCode(Product::ENTITY, 'gtin')) {
            $eavSetup->removeAttribute(Product::ENTITY, 'gtin');
        }

        $setup->endSetup();
    }
}
