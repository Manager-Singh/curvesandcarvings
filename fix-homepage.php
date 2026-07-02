<?php
use Magento\Framework\App\Bootstrap;

require __DIR__ . '/app/bootstrap.php';

$bootstrap = Bootstrap::create(BP, $_SERVER);
$objectManager = $bootstrap->getObjectManager();

$state = $objectManager->get(\Magento\Framework\App\State::class);
try {
    $state->setAreaCode('adminhtml');
} catch (\Exception $e) {
}

$page = $objectManager->create(\Magento\Cms\Model\Page::class);
$page->load('home', 'identifier');

if (!$page->getId()) {
    echo "Home page not found.\n";
    exit(1);
}

$page->setContent(
    '{{block class="Magento\\Framework\\View\\Element\\Template" name="test_view" '
    . 'template="Curvesandcarvings_Homepage::extra/home-view.phtml"}}'
);
$page->save();

echo "Home page updated.\n";
