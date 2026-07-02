<?php
declare(strict_types=1);

namespace Curvesandcarvings\Homepage\Block;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Helper\Image as ImageHelper;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Cms\Block\Block as CmsBlock;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Pricing\Helper\Data as PriceHelper;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Store\Model\StoreManagerInterface;
use Yereone\Testimonials\Model\ResourceModel\Testimonial\CollectionFactory as TestimonialCollectionFactory;

class Home extends Template
{
    private StoreManagerInterface $storeManager;
    private CollectionFactory $productCollectionFactory;
    private TestimonialCollectionFactory $testimonialCollectionFactory;
    private PriceHelper $priceHelper;
    private ResourceConnection $resourceConnection;
    private ImageHelper $imageHelper;

    public function __construct(
        Context $context,
        StoreManagerInterface $storeManager,
        CollectionFactory $productCollectionFactory,
        TestimonialCollectionFactory $testimonialCollectionFactory,
        PriceHelper $priceHelper,
        ResourceConnection $resourceConnection,
        ImageHelper $imageHelper,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->storeManager = $storeManager;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->testimonialCollectionFactory = $testimonialCollectionFactory;
        $this->priceHelper = $priceHelper;
        $this->resourceConnection = $resourceConnection;
        $this->imageHelper = $imageHelper;
    }

    public function getMediaUrl(string $path): string
    {
        return $this->storeManager->getStore()->getBaseUrl(UrlInterface::URL_TYPE_MEDIA) . ltrim($path, '/');
    }

    public function getCmsBlockHtml(string $identifier): string
    {
        $block = $this->getLayout()->createBlock(CmsBlock::class);
        if (!$block) {
            return '';
        }
        return $block->setBlockId($identifier)->toHtml();
    }

    /**
     * @return \Yereone\Testimonials\Model\ResourceModel\Testimonial\Collection
     */
    public function getTestimonials()
    {
        $collection = $this->testimonialCollectionFactory->create();
        $collection->addFieldToFilter('status_id', 1);
        $collection->setOrder('id', 'DESC');
        $collection->setPageSize(8);
        return $collection;
    }

    public function getTestimonialImageUrl(?string $image): string
    {
        if (!$image) {
            return $this->getMediaUrl('yereone/testimonials/images/placeholder.jpg');
        }
        return $this->getMediaUrl('yereone/testimonials/images' . $image);
    }

    /**
     * @param string[] $skuFragments
     * @return ProductInterface[]
     */
    public function getProductsBySkuFragments(array $skuFragments): array
    {
        if ($skuFragments === []) {
            return [];
        }

        $collection = $this->productCollectionFactory->create();
        $collection->addAttributeToSelect(['name', 'price', 'special_price', 'thumbnail', 'url_key', 'sku']);
        $collection->addStoreFilter();
        $collection->setVisibility([
            Visibility::VISIBILITY_IN_CATALOG,
            Visibility::VISIBILITY_BOTH,
        ]);

        $conditions = [];
        foreach ($skuFragments as $fragment) {
            $conditions[] = ['like' => '%' . $fragment . '%'];
        }
        $collection->addAttributeToFilter('sku', $conditions);

        return $collection->getItems();
    }

    public function getProductImageUrl(ProductInterface $product): string
    {
        return $this->imageHelper->init($product, 'category_page_grid')->getUrl();
    }

    public function getFormattedPrice($amount): string
    {
        return $this->priceHelper->currency($amount, true, false);
    }

    /**
     * @return array<int, array{title:string,url:string,image:string,excerpt:string}>
     */
    public function getBlogPosts(): array
    {
        try {
            $connection = $this->resourceConnection->getConnection();
            if (!$connection->isTableExists('mageplaza_blog_post')) {
                return [];
            }
            $select = $connection->select()
                ->from('mageplaza_blog_post', ['name', 'url_key', 'image', 'short_description'])
                ->where('enabled = ?', 1)
                ->order('post_id DESC')
                ->limit(5);
            $rows = $connection->fetchAll($select);
            $posts = [];
            foreach ($rows as $row) {
                $posts[] = [
                    'title' => (string)$row['name'],
                    'url' => $this->getUrl('blog/post/' . $row['url_key']),
                    'image' => $row['image']
                        ? $this->getMediaUrl('mageplaza/blog/post/' . ltrim((string)$row['image'], '/'))
                        : '',
                    'excerpt' => (string)$row['short_description'],
                ];
            }
            return $posts;
        } catch (\Throwable $e) {
            return [];
        }
    }
}
