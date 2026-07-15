<?php
declare(strict_types=1);

namespace Curvesandcarvings\Variations\Model;

use Curvesandcarvings\Variations\Setup\Patch\Data\CreateVariationAttributeSets;
use Curvesandcarvings\Variations\Setup\Patch\Data\CreateVariationAttributes;
use Magento\Catalog\Api\ProductAttributeRepositoryInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Gallery\Processor as GalleryProcessor;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Catalog\Model\ProductFactory;
use Magento\Catalog\Model\ResourceModel\Eav\AttributeFactory as CatalogAttributeFactory;
use Magento\ConfigurableProduct\Helper\Product\Options\Factory as ConfigurableOptionsFactory;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable\Attribute as ConfigurableAttribute;
use Magento\Eav\Api\Data\AttributeInterface;
use Magento\Eav\Model\Config as EavConfig;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Filesystem;
use Magento\MediaStorage\Model\File\UploaderFactory;

class VariationManager
{
    public const MAX_IMAGES = 5;

    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var ProductFactory
     */
    private $productFactory;

    /**
     * @var ProductAttributeRepositoryInterface
     */
    private $attributeRepository;

    /**
     * @var ConfigurableOptionsFactory
     */
    private $configurableOptionsFactory;

    /**
     * @var GalleryProcessor
     */
    private $galleryProcessor;

    /**
     * @var UploaderFactory
     */
    private $uploaderFactory;

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var EavConfig
     */
    private $eavConfig;

    /**
     * @var CatalogAttributeFactory
     */
    private $catalogAttributeFactory;

    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    public function __construct(
        ProductRepositoryInterface $productRepository,
        ProductFactory $productFactory,
        ProductAttributeRepositoryInterface $attributeRepository,
        ConfigurableOptionsFactory $configurableOptionsFactory,
        GalleryProcessor $galleryProcessor,
        UploaderFactory $uploaderFactory,
        Filesystem $filesystem,
        EavConfig $eavConfig,
        CatalogAttributeFactory $catalogAttributeFactory,
        ResourceConnection $resourceConnection
    ) {
        $this->productRepository = $productRepository;
        $this->productFactory = $productFactory;
        $this->attributeRepository = $attributeRepository;
        $this->configurableOptionsFactory = $configurableOptionsFactory;
        $this->galleryProcessor = $galleryProcessor;
        $this->uploaderFactory = $uploaderFactory;
        $this->filesystem = $filesystem;
        $this->eavConfig = $eavConfig;
        $this->catalogAttributeFactory = $catalogAttributeFactory;
        $this->resourceConnection = $resourceConnection;
    }

    public function getParent(int $parentId): Product
    {
        /** @var Product $parent */
        $parent = $this->productRepository->getById($parentId, true, 0, true);
        if ($parent->getTypeId() !== Configurable::TYPE_CODE) {
            throw new LocalizedException(__('This tool is only for configurable products.'));
        }

        return $parent;
    }

    /**
     * Resolve the variation attribute for a configurable parent.
     */
    public function resolveAttributeCode(Product $parent): ?string
    {
        $used = $parent->getTypeInstance()->getConfigurableAttributesAsArray($parent);
        if ($used) {
            $first = reset($used);
            if (!empty($first['attribute_code'])) {
                return (string)$first['attribute_code'];
            }
        }

        $setName = $this->getAttributeSetName((int)$parent->getAttributeSetId());
        if ($setName && isset(CreateVariationAttributeSets::ATTRIBUTE_SETS[$setName])) {
            return CreateVariationAttributeSets::ATTRIBUTE_SETS[$setName];
        }

        $codes = array_keys(CreateVariationAttributes::ATTRIBUTES);
        foreach ($codes as $code) {
            try {
                $attr = $this->eavConfig->getAttribute(Product::ENTITY, $code);
                if ($attr && $attr->getId() && $parent->getResource()->getAttribute($code)) {
                    return $code;
                }
            } catch (\Throwable $e) {
                continue;
            }
        }

        return $codes[0] ?? null;
    }

    /**
     * @return array<int, array{value:int,label:string}>
     */
    public function getAttributeOptions(string $attributeCode): array
    {
        $attribute = $this->attributeRepository->get($attributeCode);
        $options = [];
        foreach ($attribute->getOptions() as $option) {
            $value = (int)$option->getValue();
            if (!$value) {
                continue;
            }
            $options[] = [
                'value' => $value,
                'label' => (string)$option->getLabel(),
            ];
        }

        return $options;
    }

    public function getAttributeLabel(string $attributeCode): string
    {
        if (isset(CreateVariationAttributes::ATTRIBUTES[$attributeCode]['label'])) {
            return CreateVariationAttributes::ATTRIBUTES[$attributeCode]['label'];
        }

        return (string)$this->attributeRepository->get($attributeCode)->getDefaultFrontendLabel();
    }

    public function getAttributeId(string $attributeCode): int
    {
        try {
            return (int)$this->attributeRepository->get($attributeCode)->getAttributeId();
        } catch (\Throwable $e) {
            return 0;
        }
    }

    /**
     * Create a new attribute option (and text swatch) and return its option id.
     */
    public function createAttributeOption(string $attributeCode, string $label): int
    {
        $label = trim($label);
        if ($label === '') {
            throw new LocalizedException(__('Please enter a new option label.'));
        }

        $attributeApi = $this->attributeRepository->get($attributeCode);
        foreach ($attributeApi->getOptions() as $option) {
            if (!$option->getValue()) {
                continue;
            }
            if (strcasecmp((string)$option->getLabel(), $label) === 0) {
                return (int)$option->getValue();
            }
        }

        /** @var \Magento\Catalog\Model\ResourceModel\Eav\Attribute $attribute */
        $attribute = $this->catalogAttributeFactory->create()->load((int)$attributeApi->getAttributeId());
        if (!$attribute->getId()) {
            throw new LocalizedException(__('Variation attribute "%1" was not found.', $attributeCode));
        }

        $existingCount = 0;
        foreach ($attribute->getSource()->getAllOptions(false) as $opt) {
            if (!empty($opt['value'])) {
                $existingCount++;
            }
        }

        $attribute->setOption([
            'value' => ['option_0' => [$label]],
            'order' => ['option_0' => $existingCount + 1],
        ]);
        $attribute->save();

        $this->eavConfig->clear();
        $optionId = 0;
        $attribute = $this->catalogAttributeFactory->create()->load((int)$attributeApi->getAttributeId());
        foreach ($attribute->getSource()->getAllOptions(false) as $opt) {
            if (isset($opt['label']) && strcasecmp((string)$opt['label'], $label) === 0) {
                $optionId = (int)$opt['value'];
                break;
            }
        }

        if ($optionId <= 0) {
            throw new LocalizedException(__('Could not create option "%1".', $label));
        }

        $this->ensureTextSwatch($optionId, $label);

        return $optionId;
    }

    private function ensureTextSwatch(int $optionId, string $label): void
    {
        $connection = $this->resourceConnection->getConnection();
        $swatchTable = $this->resourceConnection->getTableName('eav_attribute_option_swatch');
        $exists = $connection->fetchOne(
            $connection->select()
                ->from($swatchTable, 'swatch_id')
                ->where('option_id = ?', $optionId)
                ->where('store_id = 0')
        );
        if ($exists) {
            return;
        }

        $connection->insert($swatchTable, [
            'option_id' => $optionId,
            'store_id' => 0,
            'type' => 0,
            'value' => $label,
        ]);
    }

    /**
     * Create a new simple child and attach it to the configurable parent.
     *
     * @param array<string, mixed> $data
     * @param array<int, string> $uploadedAbsolutePaths keyed by slot 1..5
     */
    public function createVariation(int $parentId, array $data, array $uploadedAbsolutePaths = []): Product
    {
        $parent = $this->getParent($parentId);
        $attributeCode = $this->resolveAttributeCode($parent);
        if (!$attributeCode) {
            throw new LocalizedException(__('No variation attribute found for this product.'));
        }

        $sku = trim((string)($data['sku'] ?? ''));
        $name = trim((string)($data['name'] ?? ''));
        $price = (float)str_replace(',', '', (string)($data['price'] ?? '0'));
        $optionId = (int)($data['option_id'] ?? 0);
        $qty = (float)($data['qty'] ?? 100);

        if ($sku === '' || $name === '') {
            throw new LocalizedException(__('SKU and Name are required for a new variation.'));
        }
        if ($optionId <= 0) {
            throw new LocalizedException(__('Please select a variation option.'));
        }
        if ($price < 0) {
            throw new LocalizedException(__('Price must be zero or greater.'));
        }

        $this->assertOptionAvailable($parent, $attributeCode, $optionId);

        $child = null;
        try {
            $existing = $this->productRepository->get($sku, true, 0, true);
            // Reuse an orphan simple with this SKU if it is not linked to another configurable.
            if ($existing->getTypeId() !== \Magento\Catalog\Model\Product\Type::TYPE_SIMPLE) {
                throw new LocalizedException(__('A product with SKU "%1" already exists.', $sku));
            }
            $child = $existing;
        } catch (NoSuchEntityException $e) {
            // SKU is available — create new
        }

        if ($child === null) {
            /** @var Product $child */
            $child = $this->productFactory->create();
            $child->setSku($sku);
            $child->setTypeId(\Magento\Catalog\Model\Product\Type::TYPE_SIMPLE);
        }

        $child->setName($name);
        $child->setAttributeSetId((int)$parent->getAttributeSetId());
        $child->setStatus(Status::STATUS_ENABLED);
        $child->setVisibility(Visibility::VISIBILITY_NOT_VISIBLE);
        $child->setPrice($price);
        $child->setWeight((float)($parent->getWeight() ?: 1));
        $child->setWebsiteIds($parent->getWebsiteIds() ?: [1]);
        $child->setCategoryIds([]);
        $child->setStockData([
            'use_config_manage_stock' => 1,
            'qty' => $qty,
            'is_in_stock' => $qty > 0 ? 1 : 0,
        ]);
        $this->copyRequiredAttributesFromParent($parent, $child, $attributeCode);
        $child->setData($attributeCode, $optionId);
        $child->setCanSaveCustomOptions(true);
        $child->setOptions([]);

        try {
            $child = $this->productRepository->save($child);

            $firstGalleryFile = null;
            foreach ($uploadedAbsolutePaths as $slot => $absolutePath) {
                if ($absolutePath === '') {
                    continue;
                }
                $roles = null;
                if ($firstGalleryFile === null) {
                    $roles = ['image', 'small_image', 'thumbnail'];
                }
                // 5th arg false = not disabled; roles on first image make it main.
                $savedFile = $this->galleryProcessor->addImage(
                    $child,
                    $absolutePath,
                    $roles,
                    false,
                    false
                );
                if ($firstGalleryFile === null && is_string($savedFile) && $savedFile !== '') {
                    $firstGalleryFile = $savedFile;
                }
            }

            if ($firstGalleryFile) {
                foreach (['image', 'small_image', 'thumbnail'] as $role) {
                    $child->setData($role, $firstGalleryFile);
                }
            } else {
                $this->assignMainImageRoles($child);
            }

            $child = $this->productRepository->save($child);
            // Reload so storefront jsonConfig sees roles + gallery consistently
            $child = $this->productRepository->getById((int)$child->getId(), true, 0, true);

            $this->attachChild($parent, $child, $attributeCode, $optionId);
        } catch (\Throwable $e) {
            // Keep the product if it already existed; only surface the real error.
            throw $e instanceof LocalizedException
                ? $e
                : new LocalizedException(__($e->getMessage()));
        }

        return $child;
    }

    /**
     * Update an existing child variation (sku, name, price, option, images).
     *
     * @param array<string, mixed> $data
     * @param array<int, array{remove?:bool,upload?:string,current?:string}> $slotOps
     */
    public function updateVariation(int $parentId, int $childId, array $data, array $slotOps = []): Product
    {
        $parent = $this->getParent($parentId);
        $this->assertChildOfParent($parent, $childId);

        /** @var Product $child */
        $child = $this->productRepository->getById($childId, true, 0, true);
        $attributeCode = $this->resolveAttributeCode($parent);
        $changed = false;

        if (isset($data['sku'])) {
            $sku = trim((string)$data['sku']);
            if ($sku === '') {
                throw new LocalizedException(__('SKU cannot be empty.'));
            }
            if ($sku !== (string)$child->getSku()) {
                try {
                    $existing = $this->productRepository->get($sku);
                    if ((int)$existing->getId() !== $childId) {
                        throw new LocalizedException(__('A product with SKU "%1" already exists.', $sku));
                    }
                } catch (NoSuchEntityException $e) {
                    // SKU is available
                }
                $child->setSku($sku);
                $changed = true;
            }
        }

        if (isset($data['name'])) {
            $name = trim((string)$data['name']);
            if ($name !== '' && $name !== (string)$child->getName()) {
                $child->setName($name);
                $changed = true;
            }
        }

        if (array_key_exists('price', $data) && $data['price'] !== null && $data['price'] !== '') {
            $price = (float)str_replace(',', '', (string)$data['price']);
            if ($price >= 0 && (float)$child->getPrice() !== $price) {
                $child->setPrice($price);
                $child->setSpecialPrice(null);
                $child->setData('special_from_date', null);
                $child->setData('special_to_date', null);
                $changed = true;
            }
        }

        if (!empty($data['option_id']) && $attributeCode) {
            $optionId = (int)$data['option_id'];
            if ($optionId > 0 && (int)$child->getData($attributeCode) !== $optionId) {
                $this->assertOptionAvailable($parent, $attributeCode, $optionId);
                $child->setData($attributeCode, $optionId);
                $changed = true;
            }
        }

        $imageOps = 0;
        for ($slot = 1; $slot <= self::MAX_IMAGES; $slot++) {
            $op = $slotOps[$slot] ?? null;
            if (!$op) {
                continue;
            }
            $currentFile = trim((string)($op['current'] ?? ''));
            $remove = !empty($op['remove']);
            $upload = trim((string)($op['upload'] ?? ''));

            if (!$remove && $upload === '') {
                continue;
            }

            if ($currentFile !== '') {
                $this->galleryProcessor->removeImage($child, $currentFile);
                $imageOps++;
                $changed = true;
            }

            if ($upload !== '') {
                $this->galleryProcessor->addImage($child, $upload, null, false, false);
                $imageOps++;
                $changed = true;
            }
        }

        if ($changed) {
            $this->assignMainImageRoles($child);
            $child = $this->productRepository->save($child);
            if (!empty($data['option_id']) && $attributeCode) {
                $this->refreshParentLinks($parent, $attributeCode);
            }
        }

        return $child;
    }

    /**
     * Unlink child from parent and optionally delete the simple product.
     */
    public function deleteVariation(int $parentId, int $childId, bool $deleteProduct = true): void
    {
        $parent = $this->getParent($parentId);
        $this->assertChildOfParent($parent, $childId);

        /** @var Product $child */
        $child = $this->productRepository->getById($childId, true, 0, true);
        $childSku = (string)$child->getSku();

        $remainingIds = [];
        foreach ($parent->getTypeInstance()->getUsedProducts($parent) as $used) {
            if ((int)$used->getId() === $childId) {
                continue;
            }
            $remainingIds[] = (int)$used->getId();
        }

        $attributeCode = $this->resolveAttributeCode($parent);
        $parent = $this->productRepository->getById($parentId, true, 0, true);
        $this->setConfigurableLinks($parent, $remainingIds, $attributeCode);
        $this->productRepository->save($parent);

        if ($deleteProduct) {
            $this->productRepository->deleteById($childSku);
        }
    }

    /**
     * Save uploaded $_FILES entry under pub/media/tmp (required by GalleryProcessor).
     */
    public function saveUploadedFile(string $fileId): string
    {
        $uploader = $this->uploaderFactory->create(['fileId' => $fileId]);
        $uploader->setAllowedExtensions(['jpg', 'jpeg', 'gif', 'png', 'webp']);
        $uploader->setAllowRenameFiles(true);
        $uploader->setFilesDispersion(false);

        // GalleryProcessor::addImage only accepts paths inside the media directory.
        $mediaDir = $this->filesystem->getDirectoryWrite(DirectoryList::MEDIA);
        $relativeTarget = 'tmp/cc_variation_upload';
        $mediaDir->create($relativeTarget);
        $target = $mediaDir->getAbsolutePath($relativeTarget);
        $result = $uploader->save($target);

        if (!$result || empty($result['path']) || empty($result['file'])) {
            throw new LocalizedException(__('Image upload failed for %1.', $fileId));
        }

        return rtrim((string)$result['path'], '/') . '/' . $result['file'];
    }

    public function assignMainImageRoles(Product $child): void
    {
        $firstFile = null;
        foreach ($child->getMediaGallery('images') ?: [] as $image) {
            if (!empty($image['removed']) || empty($image['file'])) {
                continue;
            }
            $firstFile = (string)$image['file'];
            break;
        }

        foreach (['image', 'small_image', 'thumbnail'] as $role) {
            $child->setData($role, $firstFile ?: 'no_selection');
        }
    }

    private function attachChild(
        Product $parent,
        Product $child,
        string $attributeCode,
        int $optionId
    ): void {
        $parent = $this->productRepository->getById((int)$parent->getId(), true, 0, true);
        $childIds = [];
        foreach ($parent->getTypeInstance()->getUsedProducts($parent) as $used) {
            $childIds[] = (int)$used->getId();
        }
        if (!in_array((int)$child->getId(), $childIds, true)) {
            $childIds[] = (int)$child->getId();
        }

        $this->setConfigurableLinks($parent, $childIds, $attributeCode);
        $this->productRepository->save($parent);
    }

    private function refreshParentLinks(Product $parent, string $attributeCode): void
    {
        $parent = $this->productRepository->getById((int)$parent->getId(), true, 0, true);
        $childIds = [];
        foreach ($parent->getTypeInstance()->getUsedProducts($parent) as $used) {
            $childIds[] = (int)$used->getId();
        }
        $this->setConfigurableLinks($parent, $childIds, $attributeCode);
        $this->productRepository->save($parent);
    }

    /**
     * @param int[] $childIds
     */
    private function setConfigurableLinks(Product $parent, array $childIds, ?string $attributeCode): void
    {
        if (!$attributeCode) {
            throw new LocalizedException(__('Variation attribute is missing on the parent product.'));
        }

        /** @var AttributeInterface $eavAttribute */
        $eavAttribute = $this->attributeRepository->get($attributeCode);
        $attributeId = (int)$eavAttribute->getAttributeId();

        $values = [];
        $seen = [];
        foreach ($childIds as $childId) {
            $child = $this->productRepository->getById((int)$childId, false, 0, true);
            $optionId = (int)$child->getData($attributeCode);
            if ($optionId <= 0 || isset($seen[$optionId])) {
                continue;
            }
            $seen[$optionId] = true;
            $label = '';
            foreach ($eavAttribute->getOptions() as $option) {
                if ((int)$option->getValue() === $optionId) {
                    $label = (string)$option->getLabel();
                    break;
                }
            }
            $values[] = [
                'label' => $label,
                'value_index' => $optionId,
            ];
        }

        $configurableAttributesData = [
            [
                ConfigurableAttribute::KEY_ATTRIBUTE_ID => $attributeId,
                ConfigurableAttribute::KEY_LABEL => $eavAttribute->getDefaultFrontendLabel(),
                ConfigurableAttribute::KEY_POSITION => 0,
                'values' => $values,
            ],
        ];

        $extension = $parent->getExtensionAttributes();
        $extension->setConfigurableProductOptions(
            $this->configurableOptionsFactory->create($configurableAttributesData)
        );
        $extension->setConfigurableProductLinks(array_values(array_unique(array_map('intval', $childIds))));
        $parent->setExtensionAttributes($extension);
        $parent->setCanSaveConfigurableAttributes(true);
    }

    private function assertChildOfParent(Product $parent, int $childId): void
    {
        foreach ($parent->getTypeInstance()->getUsedProducts($parent) as $used) {
            if ((int)$used->getId() === $childId) {
                return;
            }
        }
        throw new LocalizedException(__('Variation #%1 is not linked to this product.', $childId));
    }

    /**
     * Prevent two children sharing the same configurable option value.
     */
    private function assertOptionAvailable(Product $parent, string $attributeCode, int $optionId): void
    {
        foreach ($parent->getTypeInstance()->getUsedProducts($parent) as $used) {
            if ((int)$used->getData($attributeCode) === $optionId) {
                $label = (string)$used->getAttributeText($attributeCode);
                if ($label === '') {
                    $label = (string)$optionId;
                }
                throw new LocalizedException(__(
                    'Option "%1" is already used by variation SKU "%2". Pick a different configuration.',
                    $label,
                    $used->getSku()
                ));
            }
        }
    }

    /**
     * Copy required / inherited attributes from parent so child save passes validation
     * (e.g. Style / product_category).
     */
    private function copyRequiredAttributesFromParent(
        Product $parent,
        Product $child,
        string $variationAttributeCode
    ): void {
        $skip = [
            'sku',
            'name',
            'price',
            'special_price',
            'special_from_date',
            'special_to_date',
            'status',
            'visibility',
            'type_id',
            'attribute_set_id',
            'url_key',
            'quantity_and_stock_status',
            'category_ids',
            'media_gallery',
            'image',
            'small_image',
            'thumbnail',
            'swatch_image',
            'created_at',
            'updated_at',
            'entity_id',
            'row_id',
            'has_options',
            'required_options',
            'options_container',
            $variationAttributeCode,
        ];

        $attributeSetId = (int)$parent->getAttributeSetId();
        $attributes = $parent->getAttributes();

        foreach ($attributes as $attribute) {
            $code = (string)$attribute->getAttributeCode();
            if ($code === '' || in_array($code, $skip, true)) {
                continue;
            }

            // Only attributes assigned to this set
            if (!$attribute->isInSet($attributeSetId)) {
                continue;
            }

            $isRequired = (bool)$attribute->getIsRequired();
            $shouldCopy = $isRequired
                || in_array($code, [
                    'product_category', // frontend label "Style"
                    'tax_class_id',
                    'country_of_manufacture',
                    'manufacturer',
                    'material',
                    'color',
                    'size',
                ], true);

            if (!$shouldCopy) {
                continue;
            }

            $value = $parent->getData($code);
            if ($value === null || $value === '' || $value === false) {
                continue;
            }

            $child->setData($code, $value);
        }

        // Explicit Style fallback if still empty
        if (!$child->getData('product_category') && $parent->getData('product_category')) {
            $child->setData('product_category', $parent->getData('product_category'));
        }
    }

    private function getAttributeSetName(int $attributeSetId): ?string
    {
        try {
            $entityType = $this->eavConfig->getEntityType(Product::ENTITY);
            foreach ($entityType->getAttributeSetCollection() as $set) {
                if ((int)$set->getId() === $attributeSetId) {
                    return (string)$set->getAttributeSetName();
                }
            }
        } catch (\Throwable $e) {
            return null;
        }

        return null;
    }
}
