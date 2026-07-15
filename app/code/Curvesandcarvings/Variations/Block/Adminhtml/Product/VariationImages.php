<?php
declare(strict_types=1);

namespace Curvesandcarvings\Variations\Block\Adminhtml\Product;

use Curvesandcarvings\Variations\Model\VariationManager;
use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Locator\LocatorInterface;
use Magento\Catalog\Model\Product;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\Registry;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\StoreManagerInterface;

class VariationImages extends Template
{
    public const MAX_IMAGES = VariationManager::MAX_IMAGES;

    /**
     * @var Registry
     */
    private $registry;

    /**
     * @var LocatorInterface
     */
    private $locator;

    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var PriceCurrencyInterface
     */
    private $priceCurrency;

    /**
     * @var VariationManager
     */
    private $variationManager;

    public function __construct(
        Context $context,
        Registry $registry,
        LocatorInterface $locator,
        ProductRepositoryInterface $productRepository,
        StoreManagerInterface $storeManager,
        PriceCurrencyInterface $priceCurrency,
        VariationManager $variationManager,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->registry = $registry;
        $this->locator = $locator;
        $this->productRepository = $productRepository;
        $this->storeManager = $storeManager;
        $this->priceCurrency = $priceCurrency;
        $this->variationManager = $variationManager;
    }

    protected function _construct()
    {
        parent::_construct();
        $this->setData('cache_lifetime', null);
    }

    public function getMaxImages(): int
    {
        return self::MAX_IMAGES;
    }

    public function getProduct(): ?Product
    {
        $product = $this->registry->registry('current_product');
        if (!$product instanceof Product) {
            $product = $this->locator->getProduct();
        }
        return $product instanceof Product && (int)$product->getId() > 0 ? $product : null;
    }

    public function isConfigurable(): bool
    {
        $product = $this->getProduct();
        return $product && $product->getTypeId() === Configurable::TYPE_CODE;
    }

    public function getAttributeCode(): ?string
    {
        $product = $this->getProduct();
        if (!$product || !$this->isConfigurable()) {
            return null;
        }
        return $this->variationManager->resolveAttributeCode($product);
    }

    public function getAttributeLabel(): string
    {
        $code = $this->getAttributeCode();
        return $code ? $this->variationManager->getAttributeLabel($code) : (string)__('Option');
    }

    /**
     * @return array<int, array{value:int,label:string}>
     */
    public function getAttributeOptions(): array
    {
        $code = $this->getAttributeCode();
        return $code ? $this->variationManager->getAttributeOptions($code) : [];
    }

    /**
     * Option IDs already assigned to existing children.
     *
     * @return int[]
     */
    public function getUsedOptionIds(): array
    {
        $used = [];
        foreach ($this->getVariationRows() as $row) {
            if (!empty($row['option_id'])) {
                $used[] = (int)$row['option_id'];
            }
        }
        return array_values(array_unique($used));
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getVariationRows(): array
    {
        $product = $this->getProduct();
        if (!$product || !$this->isConfigurable()) {
            return [];
        }

        $attributeCode = $this->getAttributeCode();
        $rows = [];
        $children = $product->getTypeInstance()->getUsedProducts($product);

        foreach ($children as $child) {
            try {
                $childProduct = $this->productRepository->getById((int)$child->getId(), false, 0, true);
            } catch (\Throwable $e) {
                continue;
            }

            $images = $this->getChildImages($childProduct);
            $slots = [];
            for ($i = 0; $i < self::MAX_IMAGES; $i++) {
                $slots[] = $images[$i] ?? null;
            }

            $optionId = $attributeCode ? (int)$childProduct->getData($attributeCode) : 0;
            $optionLabel = '';
            if ($optionId > 0) {
                foreach ($this->getAttributeOptions() as $opt) {
                    if ((int)$opt['value'] === $optionId) {
                        $optionLabel = (string)$opt['label'];
                        break;
                    }
                }
            }

            $thumbUrl = '';
            foreach ($slots as $slot) {
                if (is_array($slot) && !empty($slot['url'])) {
                    $thumbUrl = (string)$slot['url'];
                    break;
                }
            }

            $rows[] = [
                'id' => (int)$childProduct->getId(),
                'sku' => (string)$childProduct->getSku(),
                'name' => (string)$childProduct->getName(),
                'option_id' => $optionId,
                'option_label' => $optionLabel,
                'thumb_url' => $thumbUrl,
                'edit_url' => $this->getUrl('catalog/product/edit', ['id' => $childProduct->getId()]),
                'delete_url' => $this->getUrl('cc_variations/product/delete', [
                    'parent_id' => $product->getId(),
                    'child_id' => $childProduct->getId(),
                ]),
                'count' => count($images),
                'slots' => $slots,
                'price' => $this->formatPriceInput((float)$childProduct->getPrice()),
            ];
        }

        return $rows;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getChildImages(Product $product): array
    {
        $images = [];
        foreach ($product->getMediaGallery('images') ?: [] as $image) {
            if (!empty($image['removed']) || empty($image['file'])) {
                continue;
            }
            $file = (string)$image['file'];
            $roles = 0;
            foreach (['image', 'small_image', 'thumbnail'] as $role) {
                if ((string)$product->getData($role) === $file) {
                    $roles++;
                }
            }
            $images[] = [
                'file' => $file,
                'position' => (int)($image['position'] ?? 0),
                'roles' => $roles,
                'url' => $this->getMediaUrl($file),
                'is_main' => $roles > 0,
            ];
        }

        usort($images, static function (array $a, array $b): int {
            if ($a['roles'] !== $b['roles']) {
                return $b['roles'] <=> $a['roles'];
            }
            return $a['position'] <=> $b['position'];
        });

        return array_slice($images, 0, self::MAX_IMAGES);
    }

    public function getSaveUrl(): string
    {
        return $this->getUrl('cc_variations/product/save');
    }

    /**
     * Admin URL to edit the variation attribute (options / label).
     */
    public function getAttributeEditUrl(): string
    {
        $attributeId = $this->getAttributeId();
        if ($attributeId <= 0) {
            return '';
        }

        return $this->getUrl('catalog/product_attribute/edit', ['attribute_id' => $attributeId]);
    }

    public function getAttributeId(): int
    {
        $code = $this->getAttributeCode();
        if (!$code) {
            return 0;
        }

        return $this->variationManager->getAttributeId($code);
    }

    public function getCurrencySymbol(): string
    {
        return $this->priceCurrency->getCurrencySymbol();
    }

    public function getSuggestedNewSku(): string
    {
        $product = $this->getProduct();
        if (!$product) {
            return '';
        }
        return (string)$product->getSku() . '-VAR' . (count($this->getVariationRows()) + 1);
    }

    private function formatPriceInput(float $price): string
    {
        if ($price <= 0) {
            return '';
        }

        return $price == floor($price) ? (string)(int)$price : number_format($price, 2, '.', '');
    }

    private function getMediaUrl(string $file): string
    {
        $file = ltrim($file, '/');
        $store = $this->storeManager->getDefaultStoreView() ?: $this->storeManager->getStore();
        $mediaBase = $store->getBaseUrl(UrlInterface::URL_TYPE_MEDIA);

        return $mediaBase . 'catalog/product/' . $file;
    }
}
