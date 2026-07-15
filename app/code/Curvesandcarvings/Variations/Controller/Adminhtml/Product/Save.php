<?php
declare(strict_types=1);

namespace Curvesandcarvings\Variations\Controller\Adminhtml\Product;

use Curvesandcarvings\Variations\Model\VariationManager;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;

class Save extends Action implements HttpPostActionInterface
{
    public const ADMIN_RESOURCE = 'Magento_Catalog::products';

    /**
     * @var VariationManager
     */
    private $variationManager;

    /**
     * @var JsonFactory
     */
    private $jsonFactory;

    public function __construct(
        Context $context,
        VariationManager $variationManager,
        JsonFactory $jsonFactory
    ) {
        parent::__construct($context);
        $this->variationManager = $variationManager;
        $this->jsonFactory = $jsonFactory;
    }

    public function execute()
    {
        $parentId = (int)$this->getRequest()->getParam('parent_id');
        $isAjax = $this->getRequest()->isXmlHttpRequest()
            || (string)$this->getRequest()->getHeader('X-Requested-With') === 'XMLHttpRequest';
        $json = $this->jsonFactory->create();

        if (!$parentId) {
            $message = (string)__('Parent product is missing.');
            if ($isAjax) {
                return $json->setData(['success' => false, 'message' => $message]);
            }
            $this->messageManager->addErrorMessage($message);
            return $this->resultRedirectFactory->create()->setPath('catalog/product/');
        }

        try {
            $parent = $this->variationManager->getParent($parentId);
            $updated = 0;
            $created = 0;

            $childIds = [];
            foreach ($parent->getTypeInstance()->getUsedProducts($parent) as $child) {
                $childIds[] = (int)$child->getId();
            }

            foreach ($childIds as $childId) {
                $slotOps = [];
                for ($slot = 1; $slot <= VariationManager::MAX_IMAGES; $slot++) {
                    $fileKey = 'variation_' . $childId . '_' . $slot;
                    $currentFile = trim((string)$this->getRequest()->getParam(
                        'variation_file_' . $childId . '_' . $slot,
                        ''
                    ));
                    $remove = (bool)$this->getRequest()->getParam(
                        'variation_remove_' . $childId . '_' . $slot
                    );
                    $uploadPath = '';
                    if (!empty($_FILES[$fileKey]['name'])) {
                        $uploadPath = $this->variationManager->saveUploadedFile($fileKey);
                    }
                    if ($remove || $uploadPath !== '') {
                        $slotOps[$slot] = [
                            'current' => $currentFile,
                            'remove' => $remove,
                            'upload' => $uploadPath,
                        ];
                    }
                }

                $data = [
                    'sku' => $this->getRequest()->getParam('variation_sku_' . $childId),
                    'name' => $this->getRequest()->getParam('variation_name_' . $childId),
                    'price' => $this->getRequest()->getParam('variation_price_' . $childId),
                    'option_id' => $this->getRequest()->getParam('variation_option_' . $childId),
                ];

                $hasData = ($data['sku'] !== null && $data['sku'] !== '')
                    || ($data['name'] !== null && $data['name'] !== '')
                    || ($data['price'] !== null && $data['price'] !== '')
                    || !empty($data['option_id'])
                    || $slotOps;

                if (!$hasData) {
                    continue;
                }

                $this->variationManager->updateVariation($parentId, $childId, $data, $slotOps);
                $updated++;
            }

            $newSku = trim((string)$this->getRequest()->getParam('new_sku', ''));
            $newName = trim((string)$this->getRequest()->getParam('new_name', ''));
            $newPrice = $this->getRequest()->getParam('new_price');
            $newOption = (int)$this->getRequest()->getParam('new_option_id', 0);
            $newOptionLabel = trim((string)$this->getRequest()->getParam('new_option_label', ''));

            if ($newSku !== '' || $newName !== '' || $newOption > 0 || $newOptionLabel !== '') {
                if ($newOptionLabel !== '') {
                    $attrCode = $this->variationManager->resolveAttributeCode($parent);
                    if (!$attrCode) {
                        throw new LocalizedException(__('No variation attribute found for this product.'));
                    }
                    $newOption = $this->variationManager->createAttributeOption($attrCode, $newOptionLabel);
                }

                $uploads = [];
                for ($slot = 1; $slot <= VariationManager::MAX_IMAGES; $slot++) {
                    $fileKey = 'new_variation_' . $slot;
                    if (!empty($_FILES[$fileKey]['name'])) {
                        $uploads[$slot] = $this->variationManager->saveUploadedFile($fileKey);
                    }
                }

                $this->variationManager->createVariation(
                    $parentId,
                    [
                        'sku' => $newSku,
                        'name' => $newName !== '' ? $newName : $newSku,
                        'price' => $newPrice,
                        'option_id' => $newOption,
                        'qty' => $this->getRequest()->getParam('new_qty', 100),
                    ],
                    $uploads
                );
                $created++;
            }

            if ($created > 0 || $updated > 0) {
                $parts = [];
                if ($created > 0) {
                    $parts[] = (string)__('Added %1 new variation(s).', $created);
                }
                if ($updated > 0) {
                    $parts[] = (string)__('Updated %1 variation(s).', $updated);
                }
                $message = implode(' ', $parts);
                $this->messageManager->addSuccessMessage($message);
            } else {
                $message = (string)__(
                    'No changes saved. Edit a variation or fill the Add New Variation form.'
                );
                $this->messageManager->addNoticeMessage($message);
            }

            if ($isAjax) {
                return $json->setData([
                    'success' => true,
                    'message' => $message,
                    'created' => $created,
                    'updated' => $updated,
                ]);
            }
        } catch (NoSuchEntityException | LocalizedException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
            if ($isAjax) {
                return $json->setData(['success' => false, 'message' => $e->getMessage()]);
            }
        } catch (\Throwable $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
            if ($isAjax) {
                return $json->setData(['success' => false, 'message' => $e->getMessage()]);
            }
        }

        return $this->resultRedirectFactory->create()
            ->setPath('catalog/product/edit', ['id' => $parentId]);
    }
}
