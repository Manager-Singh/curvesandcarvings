<?php
declare(strict_types=1);

namespace Curvesandcarvings\Faq\Block\Adminhtml\Faq\Edit;

use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;

class DeleteButton extends GenericButton implements ButtonProviderInterface
{
    public function getButtonData(): array
    {
        $data = [];
        if ($this->getFaqId()) {
            $data = [
                'label' => __('Delete FAQ'),
                'class' => 'delete',
                'on_click' => sprintf(
                    "deleteConfirm('%s', '%s')",
                    __('Are you sure you want to delete this FAQ?'),
                    $this->getUrl('*/*/delete', ['faq_id' => $this->getFaqId()])
                ),
                'sort_order' => 20,
            ];
        }
        return $data;
    }
}
