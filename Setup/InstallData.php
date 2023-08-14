<?php

namespace Shoparize\Partner\Setup;

use Magento\Catalog\Model\Product;
use Magento\Eav\Model\Config;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Setup\InstallDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Validator\ValidateException;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;

class InstallData implements InstallDataInterface
{

    protected EavSetupFactory $eavSetupFactory;
    protected Config $eavConfig;
    protected ScopeConfigInterface $scopeConfig;
    protected WriterInterface $configWriter;

    /**
     * @param EavSetupFactory $eavSetupFactory
     * @param Config $eavConfig
     * @param WriterInterface $configWriter
     */
    public function __construct(
        EavSetupFactory $eavSetupFactory,
        Config $eavConfig,
        WriterInterface $configWriter
    ) {
        $this->eavSetupFactory = $eavSetupFactory;
        $this->eavConfig = $eavConfig;
        $this->configWriter = $configWriter;
    }

    /**
     * @param ModuleDataSetupInterface $setup
     * @param ModuleContextInterface $context
     * @return void
     * @throws LocalizedException
     * @throws ValidateException
     */
    public function install(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        $eavSetup = $this->eavSetupFactory->create(['setup' => $setup]);

        $group = 'Shoparize Partner Information';
        $eavSetup->addAttribute(
            Product::ENTITY,
            'size_unit',
            [
                'type' => 'varchar',
                'label' => 'Size Unit',
                'input' => 'text',
                'required' => false,
                'sort_order' => 3,
                'global' => ScopedAttributeInterface::SCOPE_STORE,
                'group' => $group,
            ]
        );

        $eavSetup->addAttribute(
            Product::ENTITY,
            'shipping_length',
            [
                'type' => 'decimal',
                'label' => 'Shipping Length',
                'input' => 'text',
                'required' => false,
                'sort_order' => 3,
                'global' => ScopedAttributeInterface::SCOPE_STORE,
                'group' => $group,
            ]
        );
        $eavSetup->addAttribute(
            Product::ENTITY,
            'shipping_width',
            [
                'type' => 'decimal',
                'label' => 'Shipping Width',
                'input' => 'text',
                'required' => false,
                'sort_order' => 3,
                'global' => ScopedAttributeInterface::SCOPE_STORE,
                'group' => $group,
            ]
        );
        $eavSetup->addAttribute(
            Product::ENTITY,
            'shipping_height',
            [
                'type' => 'decimal',
                'label' => 'Shipping Height',
                'input' => 'text',
                'required' => false,
                'sort_order' => 3,
                'global' => ScopedAttributeInterface::SCOPE_STORE,
                'group' => $group,
            ]
        );
        $eavSetup->addAttribute(
            Product::ENTITY,
            'brand',
            [
                'type' => 'varchar',
                'label' => 'Brand',
                'input' => 'text',
                'required' => false,
                'sort_order' => 3,
                'global' => ScopedAttributeInterface::SCOPE_STORE,
                'group' => $group,
            ]
        );
        $eavSetup->addAttribute(
            Product::ENTITY,
            'gtin',
            [
                'type' => 'varchar',
                'label' => 'Gtin',
                'input' => 'text',
                'required' => false,
                'sort_order' => 3,
                'global' => ScopedAttributeInterface::SCOPE_STORE,
                'group' => $group,
            ]
        );

        if (!$this->isProductAttributeExists('color')) {
            $eavSetup->addAttribute(
                Product::ENTITY,
                'color',
                [
                    'type' => 'int',
                    'label' => 'Color',
                    'input' => 'select',//swatch_visual
                    'backend' => 'Magento\Eav\Model\Entity\Attribute\Backend\ArrayBackend',
                    'source' => 'Magento\Eav\Model\Entity\Attribute\Source\Table',
                    'required' => false,
                    'global' => ScopedAttributeInterface::SCOPE_GLOBAL,
                    'group' => $group,
                    'used_in_product_listing' => true,
                    'visible_on_front' => true,
                    'user_defined' => true,
                    'filterable' => 2,
                    'filterable_in_search' => true,
                    'used_for_promo_rules' => true,
                    'is_html_allowed_on_front' => true,
                    'used_for_sort_by' => true,
                    'option' => [
                        'values' => ['Red', 'Blue', 'Black'],
                    ],
                    'default' => 0,
                ]
            );
        } else {
            $attr = $this->eavConfig->getAttribute(Product::ENTITY, 'color');

            $this->configWriter->save('partner/general/color', $attr->getId());
        }

        if (!$this->isProductAttributeExists('size')) {
            $eavSetup->addAttribute(
                Product::ENTITY,
                'size',
                [
                    'type' => 'int',
                    'label' => 'Size',
                    'input' => 'select',//swatch_text
                    'backend' => 'Magento\Eav\Model\Entity\Attribute\Backend\ArrayBackend',
                    'source' => 'Magento\Eav\Model\Entity\Attribute\Source\Table',
                    'required' => false,
                    'global' => ScopedAttributeInterface::SCOPE_GLOBAL,
                    'group' => $group,
                    'visible_on_front' => true,
                    'user_defined' => true,
                    'filterable' => 2,
                    'filterable_in_search' => true,
                    'used_for_promo_rules' => true,
                    'is_html_allowed_on_front' => true,
                    'used_for_sort_by' => true,
                    'option' => [
                        'values' => ['S', 'M', 'L'],
                    ],
                    'default' => 0,
                ]
            );
        } else {
            $attr = $this->eavConfig->getAttribute(Product::ENTITY, 'size');

            $this->configWriter->save('partner/general/size', $attr->getId());
        }
    }

    public function isProductAttributeExists($field): bool
    {
        $attr = $this->eavConfig->getAttribute(Product::ENTITY, $field);

        return ($attr && $attr->getId());
    }
}
