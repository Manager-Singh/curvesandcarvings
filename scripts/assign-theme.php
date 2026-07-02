<?php
/**
 * Assign Curvesandcarvings/luma theme when Magento CLI is unavailable.
 * Usage: php scripts/assign-theme.php
 */
declare(strict_types=1);

use Magento\Framework\App\Bootstrap;

require __DIR__ . '/../app/bootstrap.php';

$bootstrap = Bootstrap::create(BP, $_SERVER);
$objectManager = $bootstrap->getObjectManager();

$state = $objectManager->get(\Magento\Framework\App\State::class);
try {
    $state->setAreaCode('adminhtml');
} catch (\Exception $e) {
    // area already set
}

/** @var \Curvesandcarvings\Theme\Setup\Patch\Data\AssignStorefrontTheme $patch */
$patch = $objectManager->get(\Curvesandcarvings\Theme\Setup\Patch\Data\AssignStorefrontTheme::class);
$patch->apply();

echo "Theme Curvesandcarvings/luma assigned to all stores.\n";
