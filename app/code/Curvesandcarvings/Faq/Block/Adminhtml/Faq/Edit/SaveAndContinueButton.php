<?php
declare(strict_types=1);

namespace Curvesandcarvings\Faq\Block\Adminhtml\Faq\Edit;

use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;

class SaveAndContinueButton extends GenericButton implements ButtonProviderInterface
{
    public function getButtonData(): array
    {
        return [
            'label' => __('Save and Continue Edit'),
            'class' => 'save',
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
            'sort_order' => 30,
        ];
    }
}
