<?php
declare(strict_types=1);

namespace Curvesandcarvings\Faq\Controller\Adminhtml\Faq;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;

class NewAction extends Action
{
    public const ADMIN_RESOURCE = 'Curvesandcarvings_Faq::faq_manage';

    public function __construct(Context $context)
    {
        parent::__construct($context);
    }

    public function execute()
    {
        return $this->resultRedirectFactory->create()->setPath('*/*/edit');
    }
}
