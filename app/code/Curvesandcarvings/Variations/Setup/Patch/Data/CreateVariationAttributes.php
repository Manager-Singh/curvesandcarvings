<?php
/**
 * Creates per-product-family variation attributes used for configurable products.
 *
 * All attributes are global dropdowns rendered as text swatches so the product
 * page shows pill-style option buttons.
 */

declare(strict_types=1);

namespace Curvesandcarvings\Variations\Setup\Patch\Data;

use Magento\Catalog\Model\Product;
use Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

class CreateVariationAttributes implements DataPatchInterface
{
    /**
     * attribute_code => [frontend label, option values]
     */
    public const ATTRIBUTES = [
        'bedroom_set_config' => [
            'label' => 'Choose Option',
            'options' => ['Bed Only', 'Bed + Dressing Table', 'Bed + Side Tables', 'Full Set'],
        ],
        'bed_size' => [
            'label' => 'Select Bed Size',
            'options' => ['Single', 'Queen', 'King', 'Custom Size'],
        ],
        'seating_capacity' => [
            'label' => 'Select Seating Capacity',
            'options' => ['4 Seater', '6 Seater', '8 Seater', '9+ Seater'],
        ],
        'dining_set_config' => [
            'label' => 'Choose Option',
            'options' => ['Table Only', 'Table + 4 Chairs', 'Table + 6 Chairs', 'Full Dining Set'],
        ],
        'sofa_configuration' => [
            'label' => 'Choose Option',
            'options' => ['1 Seater', '2 Seater', '3 Seater', 'Full Sofa Set'],
        ],
        'wardrobe_doors' => [
            'label' => 'Select Doors',
            'options' => ['2 Door', '4 Door', '6 Door'],
        ],
        'drawer_config' => [
            'label' => 'Choose Configuration',
            'options' => ['Standard', 'Large', 'With Mirror'],
        ],
    ];

    /**
     * @var ModuleDataSetupInterface
     */
    private $moduleDataSetup;

    /**
     * @var EavSetupFactory
     */
    private $eavSetupFactory;

    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        EavSetupFactory $eavSetupFactory
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->eavSetupFactory = $eavSetupFactory;
    }

    /**
     * @inheritdoc
     */
    public function apply()
    {
        $this->moduleDataSetup->getConnection()->startSetup();
        $eavSetup = $this->eavSetupFactory->create(['setup' => $this->moduleDataSetup]);

        foreach (self::ATTRIBUTES as $code => $config) {
            if ($eavSetup->getAttributeId(Product::ENTITY, $code)) {
                continue;
            }

            $eavSetup->addAttribute(
                Product::ENTITY,
                $code,
                [
                    'type' => 'int',
                    'label' => $config['label'],
                    'input' => 'select',
                    'global' => ScopedAttributeInterface::SCOPE_GLOBAL,
                    'user_defined' => true,
                    'required' => false,
                    'visible' => true,
                    'searchable' => false,
                    'filterable' => false,
                    'comparable' => false,
                    'visible_on_front' => false,
                    'used_in_product_listing' => true,
                    'is_used_in_grid' => true,
                    'is_visible_in_grid' => false,
                    'is_filterable_in_grid' => true,
                    'unique' => false,
                    'apply_to' => 'simple,virtual,configurable',
                    'option' => ['values' => $config['options']],
                    // Render as text swatch (pill buttons) on the storefront
                    'additional_data' => json_encode([
                        'swatch_input_type' => 'text',
                        'update_product_preview_image' => '1',
                        'use_product_image_for_swatch' => '0',
                    ]),
                ]
            );

            $this->createTextSwatchValues($eavSetup, $code);
        }

        $this->moduleDataSetup->getConnection()->endSetup();

        return $this;
    }

    /**
     * Text swatches only render when each option has a row in
     * eav_attribute_option_swatch; addAttribute() does not create them.
     *
     * @param \Magento\Eav\Setup\EavSetup $eavSetup
     * @param string $attributeCode
     * @return void
     */
    private function createTextSwatchValues($eavSetup, string $attributeCode): void
    {
        $connection = $this->moduleDataSetup->getConnection();
        $attributeId = (int)$eavSetup->getAttributeId(Product::ENTITY, $attributeCode);
        if (!$attributeId) {
            return;
        }

        // EavSetup::addAttribute() does not map 'additional_data' into
        // catalog_eav_attribute, and without swatch_input_type=text Magento
        // renders a plain dropdown instead of swatches.
        $connection->update(
            $this->moduleDataSetup->getTable('catalog_eav_attribute'),
            [
                'additional_data' => json_encode([
                    'swatch_input_type' => 'text',
                    'update_product_preview_image' => '1',
                    'use_product_image_for_swatch' => '0',
                ]),
            ],
            ['attribute_id = ?' => $attributeId]
        );

        $optionTable = $this->moduleDataSetup->getTable('eav_attribute_option');
        $optionValueTable = $this->moduleDataSetup->getTable('eav_attribute_option_value');
        $swatchTable = $this->moduleDataSetup->getTable('eav_attribute_option_swatch');

        $options = $connection->fetchPairs(
            $connection->select()
                ->from(['o' => $optionTable], ['option_id'])
                ->joinLeft(
                    ['v' => $optionValueTable],
                    'v.option_id = o.option_id AND v.store_id = 0',
                    ['value']
                )
                ->where('o.attribute_id = ?', $attributeId)
        );

        foreach ($options as $optionId => $label) {
            $exists = $connection->fetchOne(
                $connection->select()
                    ->from($swatchTable, 'swatch_id')
                    ->where('option_id = ?', (int)$optionId)
                    ->where('store_id = 0')
            );
            if (!$exists) {
                $connection->insert($swatchTable, [
                    'option_id' => (int)$optionId,
                    'store_id' => 0,
                    'type' => 0, // textual swatch
                    'value' => (string)$label,
                ]);
            }
        }
    }

    /**
     * @inheritdoc
     */
    public static function getDependencies()
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public function getAliases()
    {
        return [];
    }
}
