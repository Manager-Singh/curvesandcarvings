<?php
declare(strict_types=1);

namespace Curvesandcarvings\Theme\ViewModel;

use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Store\Model\StoreManagerInterface;

class MediaUrl implements ArgumentInterface
{
    public function __construct(
        private readonly StoreManagerInterface $storeManager
    ) {
    }

    public function getUrl(string $path = ''): string
    {
        $base = $this->storeManager->getStore()->getBaseUrl(UrlInterface::URL_TYPE_MEDIA);
        return $path === '' ? $base : $base . ltrim($path, '/');
    }
}
