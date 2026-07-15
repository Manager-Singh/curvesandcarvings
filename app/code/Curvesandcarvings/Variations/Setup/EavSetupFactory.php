<?php
declare(strict_types=1);

namespace Curvesandcarvings\Variations\Setup;

use Magento\Eav\Setup\EavSetup;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;

/**
 * Local factory — avoids Magento\Eav\Setup\EavSetupFactory when generated code
 * cannot be written (www-data owned generated/code).
 */
class EavSetupFactory
{
    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    public function __construct(ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    /**
     * @param array{setup?: ModuleDataSetupInterface} $data
     */
    public function create(array $data = []): EavSetup
    {
        return $this->objectManager->create(EavSetup::class, $data);
    }
}
