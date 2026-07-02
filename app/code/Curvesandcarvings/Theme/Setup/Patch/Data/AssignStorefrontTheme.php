<?php
declare(strict_types=1);

namespace Curvesandcarvings\Theme\Setup\Patch\Data;

use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Theme\Model\ResourceModel\Theme\CollectionFactory as ThemeCollectionFactory;
use Magento\Theme\Model\Theme\Registration;

class AssignStorefrontTheme implements DataPatchInterface
{
    private const THEME_PATH = 'Curvesandcarvings/luma';

    public function __construct(
        private readonly ThemeCollectionFactory $themeCollectionFactory,
        private readonly Registration $themeRegistration,
        private readonly WriterInterface $configWriter,
        private readonly StoreManagerInterface $storeManager
    ) {
    }

    public function apply(): void
    {
        $this->themeRegistration->register();

        $theme = $this->themeCollectionFactory->create()
            ->addFieldToFilter('theme_path', self::THEME_PATH)
            ->getFirstItem();

        if (!$theme->getId()) {
            return;
        }

        $themeId = (string) $theme->getId();

        $this->configWriter->save('design/theme/theme_id', $themeId, ScopeInterface::SCOPE_DEFAULT, 0);

        foreach ($this->storeManager->getStores() as $store) {
            $this->configWriter->save(
                'design/theme/theme_id',
                $themeId,
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
