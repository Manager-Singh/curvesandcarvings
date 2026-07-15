<?php
declare(strict_types=1);

namespace Curvesandcarvings\Variations\Plugin\Swatches;

use Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory;
use Magento\Swatches\Helper\Data as SwatchHelper;
use Magento\Swatches\Model\AttributesList;

/**
 * Magento hides "Create New Value" for ALL swatch attributes.
 * Text swatches are safe to create inline — restore the button for them.
 */
class AttributesListPlugin
{
    /**
     * @var CollectionFactory
     */
    private $collectionFactory;

    /**
     * @var SwatchHelper
     */
    private $swatchHelper;

    public function __construct(
        CollectionFactory $collectionFactory,
        SwatchHelper $swatchHelper
    ) {
        $this->collectionFactory = $collectionFactory;
        $this->swatchHelper = $swatchHelper;
    }

    /**
     * @param AttributesList $subject
     * @param array $result
     * @param array $ids
     * @return array
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterGetAttributes(AttributesList $subject, array $result, array $ids): array
    {
        if (!$result || !$ids) {
            return $result;
        }

        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter('main_table.attribute_id', ['in' => array_map('intval', $ids)]);

        $textSwatchIds = [];
        foreach ($collection as $attribute) {
            if ($this->swatchHelper->isTextSwatch($attribute)) {
                $textSwatchIds[(int)$attribute->getId()] = true;
            }
        }

        foreach ($result as &$row) {
            $id = (int)($row['id'] ?? 0);
            if (isset($textSwatchIds[$id])) {
                $row['canCreateOption'] = true;
            }
        }
        unset($row);

        return $result;
    }
}
