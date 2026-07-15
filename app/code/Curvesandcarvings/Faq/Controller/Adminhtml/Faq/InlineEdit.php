<?php
declare(strict_types=1);

namespace Curvesandcarvings\Faq\Controller\Adminhtml\Faq;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\View\LayoutFactory;

class InlineEdit extends Action
{
    public const ADMIN_RESOURCE = 'Curvesandcarvings_Faq::faq_manage';

    public function __construct(
        Context $context,
        private readonly JsonFactory $jsonFactory,
        private readonly \Curvesandcarvings\Faq\Model\FaqFactory $faqFactory
    ) {
        parent::__construct($context);
    }

    public function execute()
    {
        $resultJson = $this->jsonFactory->create();
        $error = false;
        $messages = [];
        $postItems = $this->getRequest()->getParam('items', []);

        if (!count($postItems)) {
            $messages[] = __('Please correct the data sent.');
            $error = true;
        } else {
            foreach ($postItems as $faqId => $itemData) {
                try {
                    $faq = $this->faqFactory->create()->load($faqId);
                    $faq->addData($itemData);
                    $faq->save();
                } catch (\Throwable $e) {
                    $messages[] = __('[FAQ ID: %1] %2', $faqId, $e->getMessage());
                    $error = true;
                }
            }
        }

        return $resultJson->setData(['messages' => $messages, 'error' => $error]);
    }
}
