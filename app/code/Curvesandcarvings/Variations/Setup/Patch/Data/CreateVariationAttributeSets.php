<?php
/**
 * Creates one attribute set per furniture family, cloned from the store's
 * main "india" set, each extended with its family-specific variation attribute
 * in a dedicated "Variations" group.
 */

declare(strict_types=1);

namespace Curvesandcarvings\Variations\Setup\Patch\Data;

use Magento\Catalog\Model\Product;
use Magento\Eav\Model\Entity\Attribute\SetFactory as AttributeSetFactory;
use Curvesandcarvings\Variations\Setup\EavSetupFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

class CreateVariationAttributeSets implements DataPatchInterface
{
    /**
     * attribute set name => variation attribute code
     */
    public const ATTRIBUTE_SETS = [
        'India - Bedroom Sets' => 'bedroom_set_config',
        'India - Beds' => 'bed_size',
        'India - Dining Tables' => 'seating_capacity',
        'India - Dining Sets' => 'dining_set_config',
        'India - Sofas' => 'sofa_configuration',
        'India - Wardrobes' => 'wardrobe_doors',
        'India - Chests' => 'drawer_config',
    ];

    private const SKELETON_SET_NAME = 'india';
    private const GROUP_NAME = 'Variations';

    /**
     * @var ModuleDataSetupInterface
     */
    private $moduleDataSetup;

    /**
     * @var EavSetupFactory
     */
    private $eavSetupFactory;

    /**
     * @var AttributeSetFactory
     */
    private $attributeSetFactory;

    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        EavSetupFactory $eavSetupFactory,
        AttributeSetFactory $attributeSetFactory
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->eavSetupFactory = $eavSetupFactory;
        $this->attributeSetFactory = $attributeSetFactory;
    }

    /**
     * @inheritdoc
     */
    public function apply()
    {
        $this->moduleDataSetup->getConnection()->startSetup();
        $eavSetup = $this->eavSetupFactory->create(['setup' => $this->moduleDataSetup]);

        $entityTypeId = (int)$eavSetup->getEntityTypeId(Product::ENTITY);
        $skeletonSetId = $eavSetup->getAttributeSetId($entityTypeId, self::SKELETON_SET_NAME);
        if (!$skeletonSetId) {
            // Fall back to the default set if "india" is missing (e.g. fresh installs)
            $skeletonSetId = $eavSetup->getDefaultAttributeSetId($entityTypeId);
        }

        foreach (self::ATTRIBUTE_SETS as $setName => $attributeCode) {
            $existingSetId = $this->getAttributeSetIdByName($eavSetup, $entityTypeId, $setName);
            if (!$existingSetId) {
                $attributeSet = $this->attributeSetFactory->create();
                $attributeSet->setData([
                    'attribute_set_name' => $setName,
                    'entity_type_id' => $entityTypeId,
                    'sort_order' => 100,
                ]);
                $attributeSet->validate();
                $attributeSet->save();
                $attributeSet->initFromSkeleton($skeletonSetId);
                $attributeSet->save();
                $setId = (int)$attributeSet->getId();
            } else {
                $setId = $existingSetId;
            }

            $eavSetup->addAttributeGroup($entityTypeId, $setId, self::GROUP_NAME, 15);
            $eavSetup->addAttributeToSet($entityTypeId, $setId, self::GROUP_NAME, $attributeCode, 10);
        }

        $this->moduleDataSetup->getConnection()->endSetup();

        return $this;
    }

    /**
     * @param \Magento\Eav\Setup\EavSetup $eavSetup
     * @param int $entityTypeId
     * @param string $setName
     * @return int|null
     */
    private function getAttributeSetIdByName($eavSetup, int $entityTypeId, string $setName): ?int
    {
        $connection = $this->moduleDataSetup->getConnection();
        $select = $connection->select()
            ->from($this->moduleDataSetup->getTable('eav_attribute_set'), 'attribute_set_id')
            ->where('entity_type_id = ?', $entityTypeId)
            ->where('attribute_set_name = ?', $setName);
        $id = $connection->fetchOne($select);

        return $id ? (int)$id : null;
    }

    /**
     * @inheritdoc
     */
    public static function getDependencies()
    {
        return [CreateVariationAttributes::class];
    }

    /**
     * @inheritdoc
     */
    public function getAliases()
    {
        return [];
    }
}
