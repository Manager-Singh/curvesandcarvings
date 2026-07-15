<?php
declare(strict_types=1);

namespace Curvesandcarvings\Faq\Block\Adminhtml\Faq\Edit;

use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;
use Magento\Ui\Component\Control\Container;

class SaveButton extends GenericButton implements ButtonProviderInterface
{
    public function getButtonData(): array
    {
        return [
            'label' => __('Save FAQ'),
            'class' => 'save primary',
            'data_attribute' => [
                'mage-init' => [
                    'buttonAdapter' => [
                        'actions' => [
                            [
                                'targetName' => 'cc_faq_form.cc_faq_form',
                                'actionName' => 'save',
                                'params' => [false],
                            ],
                        ],
                    ],
                ],
            ],
            'class_name' => Container::SPLIT_BUTTON,
            'options' => $this->getOptions(),
            'sort_order' => 40,
        ];
    }

    private function getOptions(): array
    {
        return [
            [
                'label' => __('Save & Continue Edit'),
                'id_hard' => 'save_and_continue',
                'data_attribute' => [
                    'mage-init' => [
                        'buttonAdapter' => [
                            'actions' => [
                                [
                                    'targetName' => 'cc_faq_form.cc_faq_form',
                                    'actionName' => 'save',
                                    'params' => [true, ['back' => 'edit']],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }
}
