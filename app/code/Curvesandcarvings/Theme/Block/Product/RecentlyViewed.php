<?php
declare(strict_types=1);

namespace Curvesandcarvings\Theme\Block\Product;

use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Block\Product\AbstractProduct;
use Magento\Catalog\Block\Product\Context;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\DB\Sql\Expression;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\Url\Helper\Data as UrlHelper;
use Magento\Reports\Block\Product\Viewed as ReportsViewed;

/**
 * Recently viewed products for PDP (Figma card grid), with same-category fallback.
 */
class RecentlyViewed extends AbstractProduct
{
    private const PAGE_SIZE = 3;

    /**
     * @var CollectionFactory
     */
    private $collectionFactory;

    /**
     * @var CategoryRepositoryInterface
     */
    private $categoryRepository;

    /**
     * @var Visibility
     */
    private $visibility;

    /**
     * @var UrlHelper
     */
    private $urlHelper;

    /**
     * @var PriceCurrencyInterface
     */
    private $priceCurrency;

    /**
     * @var Collection|null
     */
    private $items;

    public function __construct(
        Context $context,
        CollectionFactory $collectionFactory,
        CategoryRepositoryInterface $categoryRepository,
        Visibility $visibility,
        UrlHelper $urlHelper,
        PriceCurrencyInterface $priceCurrency,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->collectionFactory = $collectionFactory;
        $this->categoryRepository = $categoryRepository;
        $this->visibility = $visibility;
        $this->urlHelper = $urlHelper;
        $this->priceCurrency = $priceCurrency;
        $this->setData('cache_lifetime', null);
    }

    /**
     * @return Product[]
     */
    public function getItems(): array
    {
        if ($this->items !== null) {
            return array_values($this->items->getItems());
        }

        $currentId = (int)($this->getProduct() ? $this->getProduct()->getId() : 0);
        $collection = $this->loadReportsViewed($currentId);

        if (!$collection || !$collection->getSize()) {
            $collection = $this->loadSameCategoryFallback($currentId);
        }

        $this->items = $collection ?: $this->collectionFactory->create();
        return array_values($this->items->getItems());
    }

    public function hasItems(): bool
    {
        return count($this->getItems()) > 0;
    }

    public function getAddToCartPostParams(Product $product): array
    {
        $url = $this->getAddToCartUrl($product, ['_escape' => false]);
        return [
            'action' => $url,
            'data' => [
                'product' => (int)$product->getEntityId(),
                ActionInterface::PARAM_NAME_URL_ENCODED => $this->urlHelper->getEncodedUrl($url),
            ],
        ];
    }

    public function getWishlistPostData(Product $product): string
    {
        return $this->_wishlistHelper->getAddParams($product);
    }

    public function formatPrice(float $amount): string
    {
        return $this->priceCurrency->format($amount, false);
    }

    /**
     * @return array{final: float, regular: float, discount: int, has_special: bool}
     */
    public function getPriceMeta(Product $product): array
    {
        $regular = (float)$product->getPriceInfo()->getPrice('regular_price')->getAmount()->getValue();
        $final = (float)$product->getPriceInfo()->getPrice('final_price')->getAmount()->getValue();
        $discount = 0;
        if ($regular > 0 && $final < $regular) {
            $discount = (int)round((($regular - $final) / $regular) * 100);
        }

        return [
            'final' => $final,
            'regular' => $regular,
            'discount' => $discount,
            'has_special' => $discount > 0,
        ];
    }

    public function getRatingSummary(Product $product): int
    {
        if (!$product->getRatingSummary() && method_exists($product, 'getRatingSummary')) {
            // Review summary may be attached by collection join; default display stars from percent.
        }
        $summary = $product->getRatingSummary();
        if (is_object($summary) && method_exists($summary, 'getRatingSummary')) {
            $percent = (int)$summary->getRatingSummary();
            return (int)round($percent / 20);
        }
        if (is_numeric($summary)) {
            return (int)round(((int)$summary) / 20);
        }
        // Design shows filled stars; use 4 as neutral storefront default when no reviews.
        return 4;
    }

    private function loadReportsViewed(int $excludeId): ?Collection
    {
        try {
            /** @var ReportsViewed $viewed */
            $viewed = $this->getLayout()->createBlock(ReportsViewed::class);
            $viewed->setPageSize(self::PAGE_SIZE + 2);
            $collection = $viewed->getItemsCollection();
            if (!$collection || !$collection->getSize()) {
                return null;
            }

            $filtered = $this->collectionFactory->create();
            $filtered->addAttributeToSelect($this->_catalogConfig->getProductAttributes());
            $filtered->addMinimalPrice();
            $filtered->addFinalPrice();
            $filtered->addTaxPercents();
            $filtered->addUrlRewrite();
            $ids = [];
            foreach ($collection as $item) {
                $id = (int)$item->getId();
                if ($id && $id !== $excludeId) {
                    $ids[] = $id;
                }
                if (count($ids) >= self::PAGE_SIZE) {
                    break;
                }
            }
            if (!$ids) {
                return null;
            }
            $filtered->addIdFilter($ids);
            $filtered->setVisibility($this->visibility->getVisibleInCatalogIds());
            $filtered->addAttributeToFilter('status', Status::STATUS_ENABLED);
            // Preserve viewed order
            $filtered->getSelect()->order(new Expression('FIELD(e.entity_id,' . implode(',', $ids) . ')'));
            return $filtered;
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function loadSameCategoryFallback(int $excludeId): ?Collection
    {
        $product = $this->getProduct();
        if (!$product || !$excludeId) {
            return null;
        }

        $categoryId = $this->resolveCategoryId($product);
        if (!$categoryId) {
            return null;
        }

        try {
            $category = $this->categoryRepository->get($categoryId);
        } catch (NoSuchEntityException $e) {
            return null;
        }

        $collection = $this->collectionFactory->create();
        $collection->addCategoryFilter($category);
        $collection->addAttributeToSelect($this->_catalogConfig->getProductAttributes());
        $collection->addMinimalPrice();
        $collection->addFinalPrice();
        $collection->addTaxPercents();
        $collection->addUrlRewrite();
        $collection->addAttributeToFilter('status', Status::STATUS_ENABLED);
        $collection->setVisibility($this->visibility->getVisibleInCatalogIds());
        $collection->addAttributeToFilter('entity_id', ['neq' => $excludeId]);
        $collection->addAttributeToSort('position', 'asc');
        $collection->setPageSize(self::PAGE_SIZE);
        $collection->setCurPage(1);

        return $collection->getSize() ? $collection : null;
    }

    private function resolveCategoryId(Product $product): int
    {
        $currentCategory = $this->_coreRegistry->registry('current_category');
        if ($currentCategory && (int)$currentCategory->getId()) {
            $productCategoryIds = array_map('intval', $product->getCategoryIds() ?: []);
            $currentId = (int)$currentCategory->getId();
            if (in_array($currentId, $productCategoryIds, true)) {
                return $currentId;
            }
        }

        $ids = array_map('intval', $product->getCategoryIds() ?: []);
        $ids = array_values(array_filter($ids));
        if (!$ids) {
            return 0;
        }

        $bestId = 0;
        $bestDepth = -1;
        foreach ($ids as $id) {
            try {
                $category = $this->categoryRepository->get($id);
                $depth = substr_count((string)$category->getPath(), '/');
                if ($depth > $bestDepth) {
                    $bestDepth = $depth;
                    $bestId = $id;
                }
            } catch (NoSuchEntityException $e) {
                continue;
            }
        }

        return $bestId;
    }
}
