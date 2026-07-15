<?php
declare(strict_types=1);

namespace Curvesandcarvings\Variations\Plugin\ConfigurableProduct;

use Magento\ConfigurableProduct\Controller\Adminhtml\Product\Attribute\CreateOptions;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Swatches\Model\Swatch;

/**
 * After Magento wizard creates a new option, add text-swatch row for storefront pills.
 */
class CreateOptionsPlugin
{
    /**
     * @var ResourceConnection
     */
    private $resource;

    /**
     * @var RequestInterface
     */
    private $request;

    public function __construct(
        ResourceConnection $resource,
        RequestInterface $request
    ) {
        $this->resource = $resource;
        $this->request = $request;
    }

    /**
     * @param CreateOptions $subject
     * @param callable $proceed
     * @return mixed
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundExecute(CreateOptions $subject, callable $proceed)
    {
        $result = $proceed();
        $this->ensureTextSwatchesForNewOptions();
        return $result;
    }

    private function ensureTextSwatchesForNewOptions(): void
    {
        $options = (array)$this->request->getParam('options', []);
        if (!$options) {
            return;
        }

        $connection = $this->resource->getConnection();
        $optionValueTable = $this->resource->getTableName('eav_attribute_option_value');
        $swatchTable = $this->resource->getTableName('eav_attribute_option_swatch');

        foreach ($options as $option) {
            if (empty($option['is_new']) || empty($option['label'])) {
                continue;
            }
            $label = trim((string)$option['label']);
            if ($label === '') {
                continue;
            }

            $optionId = (int)$connection->fetchOne(
                $connection->select()
                    ->from($optionValueTable, 'option_id')
                    ->where('store_id = 0')
                    ->where('value = ?', $label)
                    ->order('option_id DESC')
                    ->limit(1)
            );
            if ($optionId <= 0) {
                continue;
            }

            $exists = $connection->fetchOne(
                $connection->select()
                    ->from($swatchTable, 'swatch_id')
                    ->where('option_id = ?', $optionId)
                    ->where('store_id = 0')
            );
            if ($exists) {
                continue;
            }

            $connection->insert($swatchTable, [
                'option_id' => $optionId,
                'store_id' => 0,
                'type' => Swatch::SWATCH_TYPE_TEXTUAL,
                'value' => $label,
            ]);
        }
    }
}
