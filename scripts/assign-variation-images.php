<?php
/**
 * Assign up to 5 gallery images per configurable child variation.
 *
 * Image 1 = main (base / small / thumbnail). Images 2-5 = PDP slider thumbnails.
 *
 * Usage:
 *   php scripts/assign-variation-images.php
 *   php scripts/assign-variation-images.php --force
 *   php scripts/assign-variation-images.php --sku="C&C CAB0001-IND"
 *   php scripts/assign-variation-images.php --force --max-images=5
 *
 * Optional map file (up to 5 paths per child SKU):
 *   scripts/variation-image-map.json
 *
 * Paths in the map are relative to pub/media/catalog/product/, e.g.:
 *   "/c/_/c_c_cab0001_3_1.jpg"
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

const MAX_VARIATION_IMAGES = 5;

$defaultParentSkus = [
    'C&C BED0222-IND',
    'C&C BED0001-IND',
    'C&C DTC0005-IND',
    'C&C SOF0002-IND',
    'C&C WAR0003-IND',
    'C&C CAB0001-IND',
];

$force = in_array('--force', $argv, true);
$skuFilter = null;
$maxImages = MAX_VARIATION_IMAGES;

foreach ($argv as $arg) {
    if (str_starts_with($arg, '--sku=')) {
        $skuFilter = substr($arg, 6);
    }
    if (str_starts_with($arg, '--max-images=')) {
        $maxImages = max(1, min(MAX_VARIATION_IMAGES, (int)substr($arg, 13)));
    }
}

$parentSkus = $skuFilter ? [$skuFilter] : $defaultParentSkus;
$mapFile = dirname(__DIR__) . '/scripts/variation-image-map.json';
$imageMap = [];

if (is_readable($mapFile)) {
    $decoded = json_decode((string)file_get_contents($mapFile), true);
    if (is_array($decoded)) {
        foreach ($decoded as $sku => $paths) {
            if (str_starts_with((string)$sku, '_')) {
                continue;
            }
            if (!is_array($paths)) {
                continue;
            }
            $imageMap[$sku] = array_slice(array_values($paths), 0, $maxImages);
        }
    }
}

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

/**
 * @param Product $product
 * @return string[]
 */
function getExistingImageFiles(Product $product): array
{
    $files = [];
    foreach ($product->getMediaGallery('images') ?: [] as $image) {
        if (empty($image['removed']) && !empty($image['file'])) {
            $files[] = (string)$image['file'];
        }
    }
    return $files;
}

/**
 * @param GalleryProcessor $galleryProcessor
 * @param Product $product
 * @return void
 */
function clearGallery(GalleryProcessor $galleryProcessor, Product $product): void
{
    foreach ($product->getMediaGallery('images') ?: [] as $image) {
        if (!empty($image['value_id']) && empty($image['removed'])) {
            $galleryProcessor->removeImage($product, (string)$image['file']);
        }
    }
}

/**
 * @param string[] $files
 * @param int $maxImages
 * @param int $mainOffset
 * @return string[]
 */
function orderFilesForChild(array $files, int $maxImages, int $mainOffset): array
{
    if ($files === []) {
        return [];
    }

    $mainOffset = $mainOffset % count($files);
    $ordered = array_merge(
        array_slice($files, $mainOffset),
        array_slice($files, 0, $mainOffset)
    );

    return array_slice($ordered, 0, $maxImages);
}

echo 'Mode: ' . ($force ? 'FORCE' : 'safe') . PHP_EOL;
echo 'Max images per variation: ' . $maxImages . PHP_EOL;
echo 'Map file: ' . (is_readable($mapFile) ? basename($mapFile) . ' (' . count($imageMap) . ' SKUs)' : 'none') . PHP_EOL;

foreach ($parentSkus as $parentSku) {
    echo "=== {$parentSku} ===" . PHP_EOL;

    try {
        /** @var Product $parent */
        $parent = $productRepository->get($parentSku, false, $storeId, true);
    } catch (Throwable $e) {
        echo "  SKIP: {$e->getMessage()}" . PHP_EOL;
        continue;
    }

    if ($parent->getTypeId() !== Configurable::TYPE_CODE) {
        echo "  SKIP: not configurable" . PHP_EOL;
        continue;
    }

    $parentFiles = getParentImageFiles($parent);
    echo '  parent images: ' . count($parentFiles) . PHP_EOL;

    $children = $parent->getTypeInstance()->getUsedProducts($parent);
    if (count($children) < 1) {
        echo "  ERROR: no child products" . PHP_EOL;
        continue;
    }

    foreach ($children as $childIndex => $childProduct) {
        $childSku = $childProduct->getSku();

        try {
            /** @var Product $child */
            $child = $productRepository->get($childSku, false, $storeId, true);
        } catch (Throwable $e) {
            echo "  ERROR loading {$childSku}: {$e->getMessage()}" . PHP_EOL;
            continue;
        }

        $existingFiles = getExistingImageFiles($child);
        if ($existingFiles && !$force) {
            $count = count($existingFiles);
            $slots = $maxImages - $count;
            echo "  skip {$childSku}: already has {$count}/{$maxImages} image(s)";
            if ($slots > 0) {
                echo " — add up to {$slots} more in admin or map file";
            }
            echo PHP_EOL;
            continue;
        }

        if ($existingFiles && $force) {
            clearGallery($galleryProcessor, $child);
            $child = $productRepository->get($childSku, false, $storeId, true);
            echo "  cleared gallery for {$childSku}" . PHP_EOL;
        }

        if (isset($imageMap[$childSku]) && $imageMap[$childSku]) {
            $orderedFiles = array_slice($imageMap[$childSku], 0, $maxImages);
            echo "  using map for {$childSku}: " . count($orderedFiles) . " image(s)" . PHP_EOL;
        } elseif ($parentFiles) {
            $orderedFiles = orderFilesForChild($parentFiles, $maxImages, $childIndex);
        } else {
            echo "  ERROR: no images for {$childSku}" . PHP_EOL;
            continue;
        }

        $added = 0;
        try {
            foreach ($orderedFiles as $imgIndex => $file) {
                $absolutePath = resolveCatalogImagePath($mediaRead, (string)$file);
                if (!$absolutePath) {
                    echo "  WARN: missing file for {$childSku}: {$file}" . PHP_EOL;
                    continue;
                }

                $mime = mime_content_type($absolutePath) ?: '';
                if (!str_starts_with($mime, 'image/')) {
                    echo "  WARN: not an image for {$childSku}: {$file} ({$mime})" . PHP_EOL;
                    continue;
                }

                $roles = ($imgIndex === 0) ? ['image', 'small_image', 'thumbnail'] : null;
                $galleryProcessor->addImage($child, $absolutePath, $roles, false, false);
                $added++;
            }

            if ($added === 0) {
                echo "  ERROR: no images copied to {$childSku}" . PHP_EOL;
                continue;
            }

            $productRepository->save($child);
            $slider = max(0, $added - 1);
            $remaining = max(0, $maxImages - $added);
            echo "  assigned {$added}/{$maxImages} to {$childSku} (1 main + {$slider} slider)";
            if ($remaining > 0) {
                echo " — room for {$remaining} more in admin/map";
            }
            echo PHP_EOL;
        } catch (Throwable $e) {
            echo "  ERROR on {$childSku}: {$e->getMessage()}" . PHP_EOL;
        }
    }
}

echo PHP_EOL . 'Reindexing...' . PHP_EOL;
/** @var \Magento\Framework\Indexer\IndexerRegistry $indexerRegistry */
$indexerRegistry = $objectManager->get(\Magento\Framework\Indexer\IndexerRegistry::class);
foreach (['catalog_product_attribute', 'catalog_product_price'] as $indexerId) {
    $indexer = $indexerRegistry->get($indexerId);
    if (!$indexer->isScheduled()) {
        $indexer->reindexAll();
        echo "  reindexed {$indexerId}" . PHP_EOL;
    }
}

echo "Done.\n";
