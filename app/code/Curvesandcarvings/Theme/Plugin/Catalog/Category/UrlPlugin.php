<?php
declare(strict_types=1);

namespace Curvesandcarvings\Theme\Plugin\Catalog\Category;

use Magento\Catalog\Model\Category;
use Magento\Framework\UrlInterface;

class UrlPlugin
{
    private const GALLERY_CATEGORY_ID = 157;

    public function __construct(
        private readonly UrlInterface $urlBuilder
    ) {
    }

    public function afterGetUrl(Category $subject, string $result): string
    {
        if ((int) $subject->getId() === self::GALLERY_CATEGORY_ID) {
            return $this->urlBuilder->getUrl('gallery');
        }

        return $result;
    }
}
