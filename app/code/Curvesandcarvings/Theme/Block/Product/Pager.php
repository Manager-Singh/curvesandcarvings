<?php
declare(strict_types=1);

namespace Curvesandcarvings\Theme\Block\Product;

use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Helper\Image as ImageHelper;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Registry;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;

/**
 * Previous / Next product links within the current (or primary) category.
 */
class Pager extends Template
{
    /**
     * @var Registry
     */
    private $registry;

    /**
     * @var CollectionFactory
     */
    private $collectionFactory;

    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var CategoryRepositoryInterface
     */
    private $categoryRepository;

    /**
     * @var Visibility
     */
    private $visibility;

    /**
     * @var ImageHelper
     */
    private $imageHelper;

    /**
     * @var array|null
     */
    private $neighbors;

    public function __construct(
        Context $context,
        Registry $registry,
        CollectionFactory $collectionFactory,
        ProductRepositoryInterface $productRepository,
        CategoryRepositoryInterface $categoryRepository,
        Visibility $visibility,
        ImageHelper $imageHelper,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->registry = $registry;
        $this->collectionFactory = $collectionFactory;
        $this->productRepository = $productRepository;
        $this->categoryRepository = $categoryRepository;
        $this->visibility = $visibility;
        $this->imageHelper = $imageHelper;
        $this->setData('cache_lifetime', null);
    }

    public function getCurrentProduct(): ?ProductInterface
    {
        $product = $this->registry->registry('current_product');
        return $product instanceof ProductInterface ? $product : null;
    }

    public function getPreviousProduct(): ?ProductInterface
    {
        return $this->getNeighbors()['prev'] ?? null;
    }

    public function getNextProduct(): ?ProductInterface
    {
        return $this->getNeighbors()['next'] ?? null;
    }

    /**
     * Short display name for pager cards (drops brand prefix, truncates).
     */
    public function getShortName(ProductInterface $product, int $maxLength = 42): string
    {
        $name = trim((string)$product->getName());
        $name = preg_replace('/^Curves\s*&\s*Carvings\s+/i', '', $name) ?: $name;
        $name = trim((string)$name);
        if (mb_strlen($name) > $maxLength) {
            $name = rtrim(mb_substr($name, 0, $maxLength - 1)) . '…';
        }
        return $name;
    }

    public function getThumbnailUrl(ProductInterface $product): string
    {
        if (!$product instanceof Product) {
            try {
                $product = $this->productRepository->getById((int)$product->getId());
            } catch (NoSuchEntityException $e) {
                return '';
            }
        }

        return $this->imageHelper
            ->init($product, 'product_thumbnail_image')
            ->keepAspectRatio(true)
            ->resize(72, 72)
            ->getUrl();
    }

    /**
     * @return array{prev: ?ProductInterface, next: ?ProductInterface}
     */
    private function getNeighbors(): array
    {
        if ($this->neighbors !== null) {
            return $this->neighbors;
        }

        $this->neighbors = ['prev' => null, 'next' => null];
        $product = $this->getCurrentProduct();
        if (!$product || !(int)$product->getId()) {
            return $this->neighbors;
        }

        $categoryId = $this->resolveCategoryId($product);
        if (!$categoryId) {
            return $this->neighbors;
        }

        try {
            $category = $this->categoryRepository->get($categoryId);
        } catch (NoSuchEntityException $e) {
            return $this->neighbors;
        }

        $collection = $this->collectionFactory->create();
        $collection->addCategoryFilter($category);
        $collection->addAttributeToFilter('status', Status::STATUS_ENABLED);
        $collection->setVisibility($this->visibility->getVisibleInCatalogIds());
        // Matches category listing: position ASC, then entity_id DESC (added by core).
        $collection->addAttributeToSort('position', 'asc');

        // getAllIds() resets ORDER BY, so middle products can look like first/last
        // and show only Previous or only Next. Keep the position-ordered select.
        $select = clone $collection->getSelect();
        $select->reset(\Magento\Framework\DB\Select::COLUMNS);
        $select->columns('e.entity_id');
        $ids = array_values(array_unique(array_map(
            'intval',
            $collection->getConnection()->fetchCol($select)
        )));

        $currentId = (int)$product->getId();
        $pos = array_search($currentId, $ids, true);
        if ($pos === false) {
            return $this->neighbors;
        }

        $count = count($ids);
        // Circular: first ↔ last so Previous and Next always wrap in the category.
        if ($count > 1) {
            $prevIndex = ($pos - 1 + $count) % $count;
            $nextIndex = ($pos + 1) % $count;
            $this->neighbors['prev'] = $this->loadProductSafe($ids[$prevIndex]);
            $this->neighbors['next'] = $this->loadProductSafe($ids[$nextIndex]);
        }

        return $this->neighbors;
    }

    private function resolveCategoryId(ProductInterface $product): int
    {
        $currentCategory = $this->registry->registry('current_category');
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

        // Prefer the deepest category (most specific path) for same-category paging.
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

    private function loadProductSafe(int $productId): ?ProductInterface
    {
        try {
            return $this->productRepository->getById($productId);
        } catch (NoSuchEntityException $e) {
            return null;
        }
    }
}
