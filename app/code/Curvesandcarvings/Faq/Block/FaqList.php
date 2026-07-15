<?php
declare(strict_types=1);

namespace Curvesandcarvings\Faq\Block;

use Curvesandcarvings\Faq\Api\Data\FaqInterface;
use Curvesandcarvings\Faq\Model\ResourceModel\Faq\Collection;
use Curvesandcarvings\Faq\Model\ResourceModel\Faq\CollectionFactory;
use Magento\Cms\Model\Page as CmsPage;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;

class FaqList extends Template
{
    private ?Collection $faqs = null;

    public function __construct(
        Context $context,
        private readonly CollectionFactory $collectionFactory,
        private readonly CmsPage $cmsPage,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    public function getLocation(): string
    {
        return (string)$this->getData('location');
    }

    public function shouldRender(): bool
    {
        $cmsIdentifier = (string)$this->getData('cms_identifier');
        if ($cmsIdentifier !== '') {
            $current = (string)$this->cmsPage->getIdentifier();
            if ($current !== $cmsIdentifier) {
                return false;
            }
        }

        return $this->getFaqs()->getSize() > 0;
    }

    public function getFaqs(): Collection
    {
        if ($this->faqs === null) {
            $this->faqs = $this->collectionFactory->create();
            $this->faqs->addActiveFilter()
                ->addLocationFilter($this->getLocation())
                ->addSortOrder();
        }
        return $this->faqs;
    }

    public function getAccordionId(): string
    {
        return 'cc-faq-accordion-' . $this->getLocation() . '-' . substr(md5(spl_object_hash($this)), 0, 6);
    }

    protected function _toHtml(): string
    {
        if (!$this->shouldRender()) {
            return '';
        }
        return parent::_toHtml();
    }
}
