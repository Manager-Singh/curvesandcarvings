<?php
declare(strict_types=1);

namespace Curvesandcarvings\Variations\Controller\Adminhtml\Product;

use Curvesandcarvings\Variations\Model\VariationManager;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Exception\LocalizedException;

class Delete extends Action implements HttpPostActionInterface
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
        $childId = (int)$this->getRequest()->getParam('child_id');
        $isAjax = $this->getRequest()->isXmlHttpRequest()
            || (string)$this->getRequest()->getHeader('X-Requested-With') === 'XMLHttpRequest';
        $json = $this->jsonFactory->create();

        if (!$parentId || !$childId) {
            $message = (string)__('Missing product or variation id.');
            if ($isAjax) {
                return $json->setData(['success' => false, 'message' => $message]);
            }
            $this->messageManager->addErrorMessage($message);
            return $this->resultRedirectFactory->create()->setPath('catalog/product/');
        }

        try {
            $this->variationManager->deleteVariation($parentId, $childId, true);
            $message = (string)__('Variation deleted.');
            $this->messageManager->addSuccessMessage($message);
            if ($isAjax) {
                return $json->setData(['success' => true, 'message' => $message]);
            }
        } catch (LocalizedException $e) {
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
