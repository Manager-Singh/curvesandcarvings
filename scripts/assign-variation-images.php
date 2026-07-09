<?php
/**
 * Copy parent gallery images to configurable child variations so swatch
 * selection updates the product gallery. Run from Magento root:
 *
 *   php scripts/assign-variation-images.php
 *
 * Safe to re-run: skips children that already have gallery images.
 */

declare(strict_types=1);

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Gallery\Processor as GalleryProcessor;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Framework\App\Bootstrap;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\State;
use Magento\Framework\Filesystem;
use Magento\Store\Model\StoreManagerInterface;

$parentSkus = [
    'C&C BED0222-IND',
    'C&C BED0001-IND',
    'C&C DTC0005-IND',
    'C&C SOF0002-IND',
    'C&C WAR0003-IND',
    'C&C CAB0001-IND',
];

require dirname(__DIR__) . '/app/bootstrap.php';

$bootstrap = Bootstrap::create(BP, $_SERVER);
$objectManager = $bootstrap->getObjectManager();

/** @var State $appState */
$appState = $objectManager->get(State::class);
$appState->setAreaCode('adminhtml');

/** @var ProductRepositoryInterface $productRepository */
$productRepository = $objectManager->get(ProductRepositoryInterface::class);
/** @var GalleryProcessor $galleryProcessor */
$galleryProcessor = $objectManager->get(GalleryProcessor::class);
/** @var Filesystem $filesystem */
$filesystem = $objectManager->get(Filesystem::class);
/** @var StoreManagerInterface $storeManager */
$storeManager = $objectManager->get(StoreManagerInterface::class);
$mediaRead = $filesystem->getDirectoryRead(DirectoryList::MEDIA);
$storeId = (int)$storeManager->getStore()->getId();

/**
 * @param \Magento\Framework\Filesystem\Directory\ReadInterface $mediaRead
 * @param string $file
 * @return string|null
 */
function resolveCatalogImagePath($mediaRead, string $file): ?string
{
    $file = (string)$file;
    $candidates = [
        'catalog/product' . $file,
        'catalog/product/' . ltrim($file, '/'),
    ];

    foreach ($candidates as $relative) {
        if ($mediaRead->isFile($relative)) {
            return $mediaRead->getAbsolutePath($relative);
        }
    }

    return null;
}

/**
 * @param Product $product
 * @return string[]
 */
function getParentImageFiles(Product $product): array
{
    $files = [];

    foreach ($product->getMediaGallery('images') ?: [] as $image) {
        if (!empty($image['removed']) || empty($image['file'])) {
            continue;
        }
        $files[] = (string)$image['file'];
    }

    if ($files) {
        return array_values(array_unique($files));
    }

    foreach ($product->getMediaGalleryEntries() ?: [] as $entry) {
        if (is_object($entry) && method_exists($entry, 'getFile')) {
            $file = (string)$entry->getFile();
        } elseif (is_array($entry) && !empty($entry['file'])) {
            $file = (string)$entry['file'];
        } else {
            continue;
        }

        if ($file !== '') {
            $files[] = $file;
        }
    }

    return array_values(array_unique($files));
}

foreach ($parentSkus as $parentSku) {
    echo "=== {$parentSku} ===\n";

    try {
        /** @var Product $parent */
        $parent = $productRepository->get($parentSku, false, $storeId, true);
    } catch (Throwable $e) {
        echo "  SKIP: {$e->getMessage()}\n";
        continue;
    }

    if ($parent->getTypeId() !== Configurable::TYPE_CODE) {
        echo "  SKIP: not configurable\n";
        continue;
    }

    $parentFiles = getParentImageFiles($parent);
    if (!$parentFiles) {
        echo "  ERROR: parent has no gallery images\n";
        continue;
    }

    echo "  parent images: " . count($parentFiles) . "\n";

    $children = $parent->getTypeInstance()->getUsedProducts($parent);
    if (count($children) < 1) {
        echo "  ERROR: no child products\n";
        continue;
    }

    $galleryCount = count($parentFiles);

    foreach ($children as $childIndex => $childProduct) {
        $childSku = $childProduct->getSku();

        try {
            /** @var Product $child */
            $child = $productRepository->get($childSku, false, $storeId, true);
        } catch (Throwable $e) {
            echo "  ERROR loading {$childSku}: {$e->getMessage()}\n";
            continue;
        }

        $existingImages = $child->getMediaGallery('images') ?: [];
        $existingImages = array_filter($existingImages, static function ($image) {
            return empty($image['removed']) && !empty($image['file']);
        });

        if ($existingImages) {
            echo "  skip {$childSku}: already has " . count($existingImages) . " image(s)\n";
            continue;
        }

        $primaryIndex = $childIndex % $galleryCount;
        $added = 0;

        try {
            foreach ($parentFiles as $imgIndex => $file) {
                $absolutePath = resolveCatalogImagePath($mediaRead, $file);

                if (!$absolutePath) {
                    echo "  WARN: missing file for {$childSku}: {$file}\n";
                    continue;
                }

                $roles = ($imgIndex === $primaryIndex) ? ['image', 'small_image', 'thumbnail'] : null;
                $galleryProcessor->addImage($child, $absolutePath, $roles, false, false);
                $added++;
            }

            if ($added === 0) {
                echo "  ERROR: no images copied to {$childSku}\n";
                continue;
            }

            $productRepository->save($child);
            echo "  assigned {$added} image(s) to {$childSku}\n";
        } catch (Throwable $e) {
            echo "  ERROR on {$childSku}: {$e->getMessage()}\n";
        }
    }
}

echo "\nReindexing catalog_product_attribute...\n";
/** @var \Magento\Framework\Indexer\IndexerRegistry $indexerRegistry */
$indexerRegistry = $objectManager->get(\Magento\Framework\Indexer\IndexerRegistry::class);
$indexer = $indexerRegistry->get('catalog_product_attribute');
if (!$indexer->isScheduled()) {
    $indexer->reindexAll();
    echo "  reindexed catalog_product_attribute\n";
}

echo "Done.\n";
