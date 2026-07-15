<?php
declare(strict_types=1);

namespace Curvesandcarvings\Faq\Controller\Adminhtml\Faq;

use Curvesandcarvings\Faq\Model\FaqFactory;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;

class Edit extends Action
{
    public const ADMIN_RESOURCE = 'Curvesandcarvings_Faq::faq_manage';

    public function __construct(
        Context $context,
        private readonly PageFactory $resultPageFactory,
        private readonly FaqFactory $faqFactory
    ) {
        parent::__construct($context);
    }

    public function execute()
    {
        $id = (int)$this->getRequest()->getParam('faq_id');
        $model = $this->faqFactory->create();

        if ($id) {
            $model->load($id);
            if (!$model->getId()) {
                $this->messageManager->addErrorMessage(__('This FAQ no longer exists.'));
                return $this->resultRedirectFactory->create()->setPath('*/*/');
            }
        }

        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('Curvesandcarvings_Faq::faq');
        $resultPage->getConfig()->getTitle()->prepend(
            $model->getId() ? __('Edit FAQ') : __('New FAQ')
        );
        return $resultPage;
    }
}
