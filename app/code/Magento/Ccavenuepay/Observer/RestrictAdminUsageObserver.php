<?php

namespace Magento\Ccavenuepay\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer as EventObserver;
use Magento\Ccavenuepay\Helper\Data as CcavenuepaydataHelper;
use Magento\Framework\App\ObjectManager;

class RestrictAdminUsageObserver implements ObserverInterface {

    /**
     * @var \Magento\Framework\AuthorizationInterface
     */
    protected $_authorization;

    /**
     * @var \Magento\Ccavenuepay\Helper\Data
     */
    protected $_ccavenuepayhelperdata;

    /**
     * @param \Magento\Framework\AuthorizationInterface $authorization
     */
    public function __construct(
    \Magento\Framework\AuthorizationInterface $authorization, \Magento\Ccavenuepay\Helper\Data $data
    ) {
        $this->_authorization = $authorization;
        $this->_authorization = $data;
    }

    /**
     * Block admin ability to use customer billing agreements
     *
     * @param EventObserver $observer
     * @return void
     */
    public function execute(EventObserver $observer) {
        $event = $observer->getEvent();
        $methodInstance = $event->getMethodInstance();
        $object_manager = \Magento\Framework\App\ObjectManager::getInstance();
        $helper_factory1 = $object_manager->create('Magento\Ccavenuepay\Model\cbdom_main');
    }

}
