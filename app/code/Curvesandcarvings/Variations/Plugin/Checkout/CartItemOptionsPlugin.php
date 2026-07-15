<?php
declare(strict_types=1);

namespace Curvesandcarvings\Variations\Plugin\Checkout;

use Magento\Catalog\Helper\Product\Configuration as ProductConfiguration;
use Magento\Catalog\Model\Product\Configuration\Item\ItemInterface;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Framework\Serialize\Serializer\Json;

/**
 * Ensure selected variation attributes appear first in cart/minicart/checkout options,
 * and append child SKU for clarity.
 */
class CartItemOptionsPlugin
{
    /**
     * @var Json
     */
    private $json;

    public function __construct(Json $json)
    {
        $this->json = $json;
    }

    /**
     * @param ProductConfiguration $subject
     * @param array $result
     * @param ItemInterface $item
     * @return array
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterGetOptions(ProductConfiguration $subject, array $result, ItemInterface $item): array
    {
        $product = $item->getProduct();
        if (!$product || $product->getTypeId() !== Configurable::TYPE_CODE) {
            return $result;
        }

        // If Magento plugin already merged attributes, just enrich with SKU.
        $hasAttributeInfo = false;
        foreach ($result as $option) {
            if (isset($option['option_id'], $option['option_value'])) {
                $hasAttributeInfo = true;
                break;
            }
        }

        if (!$hasAttributeInfo) {
            try {
                $attributes = $product->getTypeInstance()->getSelectedAttributesInfo($product);
                if ($attributes) {
                    $result = array_merge($attributes, $result);
                }
            } catch (\Throwable $e) {
                // keep original options
            }
        }

        $childSku = (string)$item->getSku();
        $parentSku = (string)$product->getData('sku');
        if ($childSku !== '' && $childSku !== $parentSku) {
            $alreadyHasSku = false;
            foreach ($result as $option) {
                if (isset($option['label']) && strcasecmp((string)$option['label'], 'SKU') === 0) {
                    $alreadyHasSku = true;
                    break;
                }
            }
            if (!$alreadyHasSku) {
                $result[] = [
                    'label' => (string)__('Variation SKU'),
                    'value' => $childSku,
                ];
            }
        }

        return $result;
    }
}
