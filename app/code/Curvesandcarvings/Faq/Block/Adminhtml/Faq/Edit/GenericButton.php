<?php
declare(strict_types=1);

namespace Curvesandcarvings\Faq\Block\Adminhtml\Faq\Edit;

use Magento\Backend\Block\Widget\Context;

class GenericButton
{
    public function __construct(
        protected readonly Context $context
    ) {
    }

    public function getFaqId(): ?int
    {
        $id = $this->context->getRequest()->getParam('faq_id');
        return $id ? (int)$id : null;
    }

    public function getUrl(string $route = '', array $params = []): string
    {
        return $this->context->getUrlBuilder()->getUrl($route, $params);
    }
}
