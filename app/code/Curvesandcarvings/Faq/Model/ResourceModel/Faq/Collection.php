<?php
declare(strict_types=1);

namespace Curvesandcarvings\Faq\Model\ResourceModel\Faq;

use Curvesandcarvings\Faq\Api\Data\FaqInterface;
use Curvesandcarvings\Faq\Model\Faq as FaqModel;
use Curvesandcarvings\Faq\Model\ResourceModel\Faq as FaqResource;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    protected $_idFieldName = FaqInterface::FAQ_ID;

    protected function _construct(): void
    {
        $this->_init(FaqModel::class, FaqResource::class);
    }

    public function addActiveFilter(): self
    {
        return $this->addFieldToFilter(FaqInterface::IS_ACTIVE, 1);
    }

    public function addLocationFilter(string $location): self
    {
        $map = [
            FaqInterface::LOCATION_PRODUCT => FaqInterface::SHOW_ON_PRODUCT,
            FaqInterface::LOCATION_ABOUT => FaqInterface::SHOW_ON_ABOUT,
            FaqInterface::LOCATION_CONTACT => FaqInterface::SHOW_ON_CONTACT,
            FaqInterface::LOCATION_HOME => FaqInterface::SHOW_ON_HOME,
        ];

        if (!isset($map[$location])) {
            $this->addFieldToFilter(FaqInterface::FAQ_ID, -1);
            return $this;
        }

        return $this->addFieldToFilter($map[$location], 1);
    }

    public function addSortOrder(): self
    {
        $this->setOrder(FaqInterface::SORT_ORDER, self::SORT_ORDER_ASC);
        $this->setOrder(FaqInterface::FAQ_ID, self::SORT_ORDER_ASC);
        return $this;
    }
}
