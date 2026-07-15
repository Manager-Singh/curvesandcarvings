<?php
declare(strict_types=1);

namespace Curvesandcarvings\Faq\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class Faq extends AbstractDb
{
    protected function _construct(): void
    {
        $this->_init('cc_faq', 'faq_id');
    }
}
