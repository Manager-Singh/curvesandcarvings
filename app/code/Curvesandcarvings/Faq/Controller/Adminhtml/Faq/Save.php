<?php
declare(strict_types=1);

namespace Curvesandcarvings\Faq\Controller\Adminhtml\Faq;

use Curvesandcarvings\Faq\Model\FaqFactory;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\Exception\LocalizedException;

class Save extends Action implements HttpPostActionInterface
{
    public const ADMIN_RESOURCE = 'Curvesandcarvings_Faq::faq_manage';

    public function __construct(
        Context $context,
        private readonly FaqFactory $faqFactory,
        private readonly DataPersistorInterface $dataPersistor
    ) {
        parent::__construct($context);
    }

    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $data = $this->getRequest()->getPostValue();

        if (!$data) {
            return $resultRedirect->setPath('*/*/');
        }

        if (empty($data['faq_id'])) {
            $data['faq_id'] = null;
        }

        $id = (int)($data['faq_id'] ?? 0);
        $model = $this->faqFactory->create();

        if ($id) {
            $model->load($id);
            if (!$model->getId()) {
                $this->messageManager->addErrorMessage(__('This FAQ no longer exists.'));
                return $resultRedirect->setPath('*/*/');
            }
        }

        foreach (['is_active', 'show_on_product', 'show_on_about', 'show_on_contact', 'show_on_home'] as $flag) {
            if (!array_key_exists($flag, $data)) {
                $data[$flag] = 0;
            } else {
                $data[$flag] = in_array($data[$flag], [1, '1', true, 'true'], true) ? 1 : 0;
            }
        }

        $data['sort_order'] = isset($data['sort_order']) ? (int)$data['sort_order'] : 0;
        $model->setData($data);

        try {
            $model->save();
            $this->messageManager->addSuccessMessage(__('You saved the FAQ.'));
            $this->dataPersistor->clear('cc_faq');

            if ($this->getRequest()->getParam('back')) {
                return $resultRedirect->setPath('*/*/edit', ['faq_id' => $model->getId()]);
            }
            return $resultRedirect->setPath('*/*/');
        } catch (LocalizedException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        } catch (\Throwable $e) {
            $this->messageManager->addExceptionMessage($e, __('Something went wrong while saving the FAQ.'));
        }

        $this->dataPersistor->set('cc_faq', $data);
        return $resultRedirect->setPath('*/*/edit', ['faq_id' => $id ?: null]);
    }
}
