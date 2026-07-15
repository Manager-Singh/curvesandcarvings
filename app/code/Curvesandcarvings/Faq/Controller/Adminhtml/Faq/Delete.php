<?php
declare(strict_types=1);

namespace Curvesandcarvings\Faq\Controller\Adminhtml\Faq;

use Curvesandcarvings\Faq\Model\FaqFactory;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;

class Delete extends Action
{
    public const ADMIN_RESOURCE = 'Curvesandcarvings_Faq::faq_manage';

    public function __construct(
        Context $context,
        private readonly FaqFactory $faqFactory
    ) {
        parent::__construct($context);
    }

    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $id = (int)$this->getRequest()->getParam('faq_id');

        if ($id) {
            try {
                $model = $this->faqFactory->create();
                $model->load($id);
                $model->delete();
                $this->messageManager->addSuccessMessage(__('You deleted the FAQ.'));
                return $resultRedirect->setPath('*/*/');
            } catch (\Throwable $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
                return $resultRedirect->setPath('*/*/edit', ['faq_id' => $id]);
            }
        }

        $this->messageManager->addErrorMessage(__('We can\'t find an FAQ to delete.'));
        return $resultRedirect->setPath('*/*/');
    }
}
