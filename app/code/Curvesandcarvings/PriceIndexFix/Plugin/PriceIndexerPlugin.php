<?php
/**
 * Prevents negative percentage custom options (e.g. a "-50% advance payment" option)
 * from lowering the indexed min_price used by layered-navigation price filtering.
 *
 * For option-bearing simple/virtual/downloadable products the index normally stores
 * min_price = final_price - 50% (cheapest option) and max_price = final_price + 2%,
 * which places products in the wrong price-filter buckets. This plugin normalizes
 * min_price and max_price back to final_price for those products after each price
 * reindex so the price filter reflects the actual selling price.
 *
 * Configurable/bundle/grouped products are never touched, so their legitimate
 * "from" (min_price < final_price) behaviour is preserved.
 */

namespace Curvesandcarvings\PriceIndexFix\Plugin;

use Magento\Catalog\Model\Indexer\Product\Price as PriceIndexer;
use Magento\Framework\App\ResourceConnection;

class PriceIndexerPlugin
{
    /**
     * @var ResourceConnection
     */
    private $resource;

    public function __construct(ResourceConnection $resource)
    {
        $this->resource = $resource;
    }

    /**
     * @param PriceIndexer $subject
     * @param mixed $result
     * @return mixed
     */
    public function afterExecuteFull(PriceIndexer $subject, $result)
    {
        $this->normalize();
        return $result;
    }

    /**
     * @param PriceIndexer $subject
     * @param mixed $result
     * @param int[] $ids
     * @return mixed
     */
    public function afterExecute(PriceIndexer $subject, $result, $ids)
    {
        $this->normalize(is_array($ids) ? $ids : [$ids]);
        return $result;
    }

    /**
     * @param PriceIndexer $subject
     * @param mixed $result
     * @param int[] $ids
     * @return mixed
     */
    public function afterExecuteList(PriceIndexer $subject, $result, array $ids)
    {
        $this->normalize($ids);
        return $result;
    }

    /**
     * @param PriceIndexer $subject
     * @param mixed $result
     * @param int $id
     * @return mixed
     */
    public function afterExecuteRow(PriceIndexer $subject, $result, $id)
    {
        $this->normalize([$id]);
        return $result;
    }

    /**
     * Normalize min/max price to final price for option-bearing products.
     *
     * @param int[]|null $ids
     * @return void
     */
    private function normalize(?array $ids = null): void
    {
        $connection = $this->resource->getConnection();
        $priceTable = $this->resource->getTableName('catalog_product_index_price');
        $entityTable = $this->resource->getTableName('catalog_product_entity');
        $optionTable = $this->resource->getTableName('catalog_product_option');

        $sql = "UPDATE {$priceTable} AS cpip"
            . " INNER JOIN {$entityTable} AS cpe ON cpe.entity_id = cpip.entity_id"
            . " INNER JOIN (SELECT DISTINCT product_id FROM {$optionTable}) AS opt"
            . " ON opt.product_id = cpip.entity_id"
            . " SET cpip.min_price = cpip.final_price, cpip.max_price = cpip.final_price"
            . " WHERE cpe.type_id IN ('simple', 'virtual', 'downloadable')"
            . " AND (cpip.min_price <> cpip.final_price OR cpip.max_price <> cpip.final_price)";

        $bind = [];
        if ($ids !== null) {
            $ids = array_filter(array_map('intval', $ids));
            if (empty($ids)) {
                return;
            }
            $sql .= ' AND cpip.entity_id IN (' . implode(',', $ids) . ')';
        }

        $connection->query($sql, $bind);
    }
}
