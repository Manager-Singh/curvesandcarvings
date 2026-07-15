<?php
declare(strict_types=1);

namespace Curvesandcarvings\Variations\Ui\DataProvider\Product\Form\Modifier;

use Curvesandcarvings\Variations\Block\Adminhtml\Product\VariationImages;
use Magento\Catalog\Model\Locator\LocatorInterface;
use Magento\Catalog\Ui\DataProvider\Product\Form\Modifier\AbstractModifier;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\ConfigurableProduct\Ui\DataProvider\Product\Form\Modifier\ConfigurablePanel;
use Magento\Ui\Component\HtmlContent;

/**
 * Injects CC variation manager and hides Magento's default variations matrix / buttons.
 */
class VariationImagesPanel extends AbstractModifier
{
    private const PANEL_NAME = 'cc_variation_images';

    /**
     * @var LocatorInterface
     */
    private $locator;

    public function __construct(LocatorInterface $locator)
    {
        $this->locator = $locator;
    }

    public function modifyData(array $data): array
    {
        return $data;
    }

    public function modifyMeta(array $meta): array
    {
        if ($this->locator->getProduct()->getTypeId() !== Configurable::TYPE_CODE) {
            return $meta;
        }

        if (!isset($meta[ConfigurablePanel::GROUP_CONFIGURABLE]['children'])) {
            return $meta;
        }

        $children = &$meta[ConfigurablePanel::GROUP_CONFIGURABLE]['children'];

        // Remove broken action-bar panel from earlier deploy if present.
        unset($children['cc_variation_actions']);

        // Hide Magento's Current Variations grid (keep in meta so associations are not wiped).
        // Note: Magento JS re-shows this when rows exist — mixin + CSS also force-hide it.
        if (isset($children[ConfigurablePanel::CONFIGURABLE_MATRIX]['arguments']['data']['config'])) {
            $cfg = &$children[ConfigurablePanel::CONFIGURABLE_MATRIX]['arguments']['data']['config'];
            $cfg['visible'] = false;
            $cfg['additionalClasses'] = trim(
                ($cfg['additionalClasses'] ?? '') . ' cc-hide-magento-variations-matrix'
            );
            unset($cfg);
        }

        // Hide Magento "Edit Configurations" / "Add Products Manually" button set + help text.
        if (isset($children['configurable_products_button_set']['arguments']['data']['config'])) {
            $cfg = &$children['configurable_products_button_set']['arguments']['data']['config'];
            $cfg['visible'] = false;
            $cfg['additionalClasses'] = trim(
                ($cfg['additionalClasses'] ?? '') . ' cc-hide-magento-variations-buttons'
            );
            unset($cfg);
        }

        if (!isset($children[self::PANEL_NAME])) {
            $layout = '<?xml version="1.0"?>'
                . '<layout xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">'
                . '<block class="' . VariationImages::class . '"'
                . ' name="cc.variation.images.manager"'
                . ' template="Curvesandcarvings_Variations::product/variation-images.phtml"/>'
                . '</layout>';

            $children[self::PANEL_NAME] = [
                'arguments' => [
                    'data' => [
                        'config' => [
                            'componentType' => HtmlContent::NAME,
                            'sortOrder' => 5,
                        ],
                    ],
                    'block' => [
                        'name' => 'cc.variation.images.manager',
                        'layout' => $layout,
                    ],
                ],
            ];
        } else {
            $children[self::PANEL_NAME]['arguments']['data']['config']['sortOrder'] = 5;
        }

        return $meta;
    }
}
