<?php
/**
 * One-time script: convert reference products into configurable examples
 * (one per furniture family). Run from Magento root:
 *
 *   php scripts/create-variation-examples.php
 *
 * Safe to re-run: skips products already configurable or when child SKUs exist.
 */

declare(strict_types=1);

use Magento\Catalog\Api\ProductAttributeRepositoryInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Visibility;
use Magento\ConfigurableProduct\Helper\Product\Options\Factory as ConfigurableOptionsFactory;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable\Attribute as ConfigurableAttribute;
use Magento\ConfigurableProduct\Model\Product\VariationHandler;
use Magento\Eav\Model\Config as EavConfig;
use Magento\Framework\App\Bootstrap;
use Magento\Framework\App\State;
use Magento\Store\Model\StoreManagerInterface;

require dirname(__DIR__) . '/app/bootstrap.php';

/** @var array<int, array<string, mixed>> $examples */
$examples = [
    [
        'sku' => 'C&C BED0222-IND',
        'attribute_set' => 'India - Bedroom Sets',
        'attribute_code' => 'bedroom_set_config',
        'variations' => [
            'BED' => 'Bed Only',
            'DRESSING' => 'Bed + Dressing Table',
            'SIDE' => 'Bed + Side Tables',
            'FULL' => 'Full Set',
        ],
    ],
    [
        'sku' => 'C&C BED0001-IND',
        'attribute_set' => 'India - Beds',
        'attribute_code' => 'bed_size',
        'variations' => [
            'SINGLE' => 'Single',
            'DOUBLE' => 'Double',
            'QUEEN' => 'Queen',
            'KING' => 'King',
            'CUSTOM' => 'Custom Size',
        ],
    ],
    [
        'sku' => 'C&C DTC0005-IND',
        'attribute_set' => 'India - Dining Tables',
        'attribute_code' => 'seating_capacity',
        'variations' => [
            '4S' => '4 Seater',
            '6S' => '6 Seater',
            '8S' => '8 Seater',
            '9PLUS' => '9+ Seater',
            '10S' => '10 Seater',
        ],
    ],
    [
        'sku' => 'C&C SOF0002-IND',
        'attribute_set' => 'India - Sofas',
        'attribute_code' => 'sofa_configuration',
        'variations' => [
            '1S' => '1 Seater',
            '2S' => '2 Seater',
            '3S' => '3 Seater',
            'FULL' => 'Full Sofa Set',
            'LSHAPE' => 'L Shape',
        ],
    ],
    [
        'sku' => 'C&C WAR0003-IND',
        'attribute_set' => 'India - Wardrobes',
        'attribute_code' => 'wardrobe_doors',
        'variations' => [
            '2D' => '2 Door',
            '4D' => '4 Door',
        ],
    ],
    [
        'sku' => 'C&C CAB0001-IND',
        'attribute_set' => 'India - Chests',
        'attribute_code' => 'drawer_config',
        'variations' => [
            'STD' => 'Standard',
            'LARGE' => 'Large',
            'MIRROR' => 'With Mirror',
        ],
    ],
];

$bootstrap = Bootstrap::create(BP, $_SERVER);
$objectManager = $bootstrap->getObjectManager();

/** @var State $appState */
$appState = $objectManager->get(State::class);
$appState->setAreaCode('adminhtml');

/** @var ProductRepositoryInterface $productRepository */
$productRepository = $objectManager->get(ProductRepositoryInterface::class);
/** @var VariationHandler $variationHandler */
$variationHandler = $objectManager->get(VariationHandler::class);
/** @var EavConfig $eavConfig */
$eavConfig = $objectManager->get(EavConfig::class);
/** @var ProductAttributeRepositoryInterface $attributeRepository */
$attributeRepository = $objectManager->get(ProductAttributeRepositoryInterface::class);
/** @var ConfigurableOptionsFactory $configurableOptionsFactory */
$configurableOptionsFactory = $objectManager->get(ConfigurableOptionsFactory::class);
/** @var StoreManagerInterface $storeManager */
$storeManager = $objectManager->get(StoreManagerInterface::class);
$storeId = (int)$storeManager->getStore()->getId();

/**
 * @param string $setName
 * @return int
 */
function getAttributeSetId(EavConfig $eavConfig, string $setName): int
{
    $entityType = $eavConfig->getEntityType(Product::ENTITY);
    $sets = $entityType->getAttributeSetCollection();
    foreach ($sets as $set) {
        if ($set->getAttributeSetName() === $setName) {
            return (int)$set->getId();
        }
    }
    throw new RuntimeException("Attribute set not found: {$setName}");
}

/**
 * @return array<string, int>
 */
function getOptionIdsByLabel(ProductAttributeRepositoryInterface $attributeRepository, string $code): array
{
    $attribute = $attributeRepository->get($code);
    $map = [];
    foreach ($attribute->getOptions() as $option) {
        if (!$option->getValue()) {
            continue;
        }
        $map[(string)$option->getLabel()] = (int)$option->getValue();
    }
    return $map;
}

/**
 * @param Product $product
 * @return void
 */
function stripCustomOptions(Product $product): void
{
    $product->setCanSaveCustomOptions(true);
    $product->setOptions([]);
}

foreach ($examples as $example) {
    $parentSku = $example['sku'];
    $attrCode = $example['attribute_code'];
    $setName = $example['attribute_set'];
    $variations = $example['variations'];

    echo "=== {$parentSku} ({$attrCode}) ===\n";

    try {
        /** @var Product $parent */
        $parent = $productRepository->get($parentSku, false, $storeId, true);
    } catch (Throwable $e) {
        echo "  SKIP: parent not found ({$e->getMessage()})\n";
        continue;
    }

    if ($parent->getTypeId() === Configurable::TYPE_CODE) {
        echo "  SKIP: already configurable\n";
        continue;
    }

    $optionIdsByLabel = getOptionIdsByLabel($attributeRepository, $attrCode);
    $attributeSetId = getAttributeSetId($eavConfig, $setName);
    $eavAttribute = $attributeRepository->get($attrCode);
    $attributeId = (int)$eavAttribute->getAttributeId();

    $childIds = [];
    $configurableValues = [];
    $productsData = [];
    $allChildrenExist = true;

    foreach ($variations as $suffix => $label) {
        if (!isset($optionIdsByLabel[$label])) {
            echo "  ERROR: option label missing in attribute: {$label}\n";
            continue 2;
        }
        $optionId = $optionIdsByLabel[$label];
        $childSku = $parentSku . '-' . $suffix;
        $configurableValues[] = ['label' => $label, 'value_index' => $optionId];

        try {
            $existing = $productRepository->get($childSku, false, $storeId, true);
            echo "  child exists: {$childSku} (id {$existing->getId()})\n";
            $existing->setAttributeSetId($attributeSetId);
            $existing->setData($attrCode, $optionId);
            $existing->setVisibility(Visibility::VISIBILITY_NOT_VISIBLE);
            $existing->setCategoryIds([]);
            stripCustomOptions($existing);
            $existing->setStatus(Status::STATUS_ENABLED);
            $productRepository->save($existing);
            $childIds[] = (int)$existing->getId();
            continue;
        } catch (Throwable $e) {
            $allChildrenExist = false;
        }

        $row = [
            'sku' => $childSku,
            'name' => $parent->getName() . ' - ' . $label,
            'price' => $parent->getPrice(),
            'weight' => $parent->getWeight() ?: 1,
            'status' => Status::STATUS_ENABLED,
            'configurable_attribute' => json_encode([$attrCode => $optionId]),
        ];
        $productsData[$suffix] = $row;
    }

    if (!$allChildrenExist && $productsData) {
        $parent->setNewVariationsAttributeSetId($attributeSetId);
        $variationHandler->prepareAttributeSet($parent);
        $generatedIds = $variationHandler->generateSimpleProducts($parent, $productsData);
        foreach ($generatedIds as $childId) {
            $child = $productRepository->getById((int)$childId);
            stripCustomOptions($child);
            $child->setCategoryIds([]);
            $productRepository->save($child);
            echo "  created child: {$child->getSku()} (id {$childId})\n";
            $childIds[] = (int)$childId;
        }
    }

    $childIds = array_values(array_unique($childIds));
    if (count($childIds) < 2) {
        echo "  ERROR: need at least 2 children, got " . count($childIds) . "\n";
        continue;
    }

    $parent = $productRepository->get($parentSku, false, $storeId, true);
    $parent->setAttributeSetId($attributeSetId);
    $parent->setTypeId(Configurable::TYPE_CODE);

    $configurableAttributesData = [
        [
            ConfigurableAttribute::KEY_ATTRIBUTE_ID => $attributeId,
            ConfigurableAttribute::KEY_LABEL => $eavAttribute->getDefaultFrontendLabel(),
            ConfigurableAttribute::KEY_POSITION => 0,
            'values' => $configurableValues,
        ],
    ];

    $extensionAttributes = $parent->getExtensionAttributes();
    $extensionAttributes->setConfigurableProductOptions(
        $configurableOptionsFactory->create($configurableAttributesData)
    );
    $extensionAttributes->setConfigurableProductLinks($childIds);
    $parent->setExtensionAttributes($extensionAttributes);
    $parent->setCanSaveConfigurableAttributes(true);

    $productRepository->save($parent);
    echo "  converted parent to configurable (id {$parent->getId()}), children: " . implode(',', $childIds) . "\n";
}

echo "\nReindexing catalog_product_price and catalogsearch_fulltext...\n";
/** @var \Magento\Framework\Indexer\IndexerRegistry $indexerRegistry */
$indexerRegistry = $objectManager->get(\Magento\Framework\Indexer\IndexerRegistry::class);
foreach (['catalog_product_price', 'catalogsearch_fulltext', 'catalog_product_attribute'] as $indexerId) {
    $indexer = $indexerRegistry->get($indexerId);
    if (!$indexer->isScheduled()) {
        $indexer->reindexAll();
        echo "  reindexed {$indexerId}\n";
    }
}

echo "Done.\n";
