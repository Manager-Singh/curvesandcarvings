<?php
declare(strict_types=1);

namespace Curvesandcarvings\Theme\Plugin\CatalogSearch\Model\ResourceModel\Fulltext;

use Magento\CatalogSearch\Model\ResourceModel\Fulltext\Collection;

/**
 * Match live MySQL search: "DTC 0730" should find DTC products, not only exact token matches.
 */
class BroadenSkuSearchCollectionPlugin
{
    public function beforeAddSearchFilter(Collection $collection, $query): ?array
    {
        $query = trim((string) $query);
        if ($query === '') {
            return null;
        }

        if (preg_match('/^([A-Za-z][A-Za-z&\-\s]*?)\s+\d[\d\s\-]*$/', $query, $matches)) {
            $alphaPart = trim($matches[1]);
            if ($alphaPart !== '' && strcasecmp($alphaPart, $query) !== 0) {
                return [$alphaPart];
            }
        }

        return null;
    }
}
