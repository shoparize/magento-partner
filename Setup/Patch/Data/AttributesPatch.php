<?php

namespace Shoparize\Partner\Setup\Patch\Data;

use Magento\Catalog\Model\Product;
use Magento\Eav\Model\Config;
use Magento\Eav\Model\Entity\Attribute\Backend\ArrayBackend;
use Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface;
use Magento\Eav\Model\Entity\Attribute\Source\Table;
use Magento\Eav\Model\ResourceModel\Entity\Attribute;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\Patch\PatchRevertableInterface;
use Magento\Framework\Validator\ValidateException;

class AttributesPatch implements DataPatchInterface, PatchRevertableInterface
{
    /**
     * @var EavSetupFactory
     */
    protected EavSetupFactory $eavSetupFactory;

    /**
     * @var Config
     */
    protected Config $eavConfig;

    /**
     * @var WriterInterface
     */
    protected WriterInterface $configWriter;

    /**
     * @var ModuleDataSetupInterface
     */
    protected ModuleDataSetupInterface $moduleDataSetup;

    /**
     * @var Attribute
     */
    protected Attribute $eavAttribute;

    /**
     * @param EavSetupFactory $eavSetupFactory
     * @param Config $eavConfig
     * @param WriterInterface $configWriter
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param Attribute $eavAttribute
     */
    public function __construct(
        EavSetupFactory $eavSetupFactory,
        Config $eavConfig,
        WriterInterface $configWriter,
        ModuleDataSetupInterface $moduleDataSetup,
        Attribute $eavAttribute
    ) {
        $this->eavSetupFactory = $eavSetupFactory;
        $this->eavConfig = $eavConfig;
        $this->configWriter = $configWriter;
        $this->moduleDataSetup = $moduleDataSetup;
        $this->eavAttribute = $eavAttribute;
    }

    /**
     * Dependencies
     *
     * @return array|string[]
     */
    public static function getDependencies()
    {
        return [];
    }

    /**
     * Aliases
     *
     * @return array|string[]
     */
    public function getAliases()
    {
        return [];
    }

    /**
     * Apply changes
     *
     * @return void
     * @throws LocalizedException
     * @throws ValidateException
     */
    public function apply()
    {
        $this->moduleDataSetup->getConnection()->startSetup();

        $eavSetup = $this->eavSetupFactory->create(['setup' => $this->moduleDataSetup]);

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

        if (!$this->isProductAttributeExists('color2')) {
            $eavSetup->addAttribute(
                Product::ENTITY,
                'color2',
                [
                    'type' => 'int',
                    'label' => 'Color',
                    'input' => 'select',//swatch_visual
                    'backend' => ArrayBackend::class,
                    'source' => Table::class,
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

        if (!$this->isProductAttributeExists('size2')) {
            $eavSetup->addAttribute(
                Product::ENTITY,
                'size2',
                [
                    'type' => 'int',
                    'label' => 'Size',
                    'input' => 'select',//swatch_text
                    'backend' => ArrayBackend::class,
                    'source' => Table::class,
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

        $this->moduleDataSetup->getConnection()->endSetup();
    }

    /**
     * Revert changes
     *
     * @return void
     */
    public function revert()
    {
        $this->moduleDataSetup->getConnection()->startSetup();

        $eavSetup = $this->eavSetupFactory->create(['setup' => $this->moduleDataSetup]);
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

        $this->moduleDataSetup->getConnection()->endSetup();
    }

    /**
     * Checks an attribute exists or not
     *
     * @param string $field
     * @return bool
     * @throws LocalizedException
     */
    public function isProductAttributeExists($field): bool
    {
        $attr = $this->eavConfig->getAttribute(Product::ENTITY, $field);

        return ($attr && $attr->getId());
    }
}
