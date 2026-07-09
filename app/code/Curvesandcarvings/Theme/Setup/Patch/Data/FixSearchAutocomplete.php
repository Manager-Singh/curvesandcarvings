<?php
declare(strict_types=1);

namespace Curvesandcarvings\Theme\Setup\Patch\Data;

use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Use catalog search_query autocomplete instead of OpenSearch phrase suggester.
 * OpenSearch suggest returns empty on Mage-OS; search_query matches live UX.
 */
class FixSearchAutocomplete implements DataPatchInterface
{
    public function __construct(
        private readonly WriterInterface $configWriter,
        private readonly StoreManagerInterface $storeManager
    ) {
    }

    public function apply(): void
    {
        foreach ($this->storeManager->getStores() as $store) {
            $this->configWriter->save(
                'catalog/search/search_suggestion_enabled',
                '0',
                ScopeInterface::SCOPE_STORES,
                (int) $store->getId()
            );
        }
    }

    public static function getDependencies(): array
    {
        return [];
    }

    public function getAliases(): array
    {
        return [];
    }
}
