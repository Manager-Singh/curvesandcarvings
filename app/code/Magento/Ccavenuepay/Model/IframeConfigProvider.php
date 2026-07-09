<?php

/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Ccavenuepay\Model;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Framework\Locale\ResolverInterface;
use Magento\Framework\UrlInterface;
use Magento\Payment\Helper\Data as PaymentHelper;

class IframeConfigProvider implements ConfigProviderInterface {

    /**
     * @var string[]
     */
    protected $methodCodes = [
        Config::METHOD_CODE
    ];

    /**
     * @var \Magento\Payment\Model\Method\AbstractMethod[]
     */
    protected $methods = [];

    /**
     * @var PaymentHelper
     */
    protected $paymentHelper;

    /**
     * @var UrlInterface
     */
    protected $urlBuilder;
    protected $logger;
    protected $paymentMethod;

    /**
     * @param PaymentHelper $paymentHelper
     * @param UrlInterface $urlBuilder
     */
    public function __construct(
    PaymentHelper $paymentHelper, UrlInterface $urlBuilder
    ) {
        $this->paymentHelper = $paymentHelper;
        $this->urlBuilder = $urlBuilder;

        foreach ($this->methodCodes as $code) {
            $this->methods[$code] = $this->paymentHelper->getMethodInstance($code);
            $paymentMethod = $this->methods[$code];
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getConfig() {
        $config = [
            'payment' => [
                'ccavenuepayIframe' => [],
            ],
        ];
        foreach ($this->methodCodes as $code) {
            if ($this->methods[$code]->isAvailable()) {
                $config['payment']['ccavenuepayIframe']['actionUrl'][$code] = $this->getFrameActionUrl($code);
            }
        }

        $paymentcode = Config::METHOD_CODE;
        //$config['payment']['ccavenuepayIframe']['merchant_id'][$paymentcode] = $this->getConfigData('merchant_id');
        return $config;
    }

    /**
     * Get frame action URL
     *
     * @param string $code
     * @return string
     */
    protected function getFrameActionUrl($code) {

        $url = '';
        switch ($code) {
            case Config::METHOD_CODE:
                $url = $this->urlBuilder->getUrl('ccavenuepay/ccavenuepay/redirect', ['_secure' => true]);
                break;
        }

        return $url;
    }

    protected function getConfigData($key) {

        $merchant_id = 2;
        $encryption_key = 'B2FC8D837D36EF00B163975053A6D23D';
        $access_code = 'E3YJNHW79AAY2FWF';
        if ($key == 'merchant_id') {
            return $merchant_id;
        } elseif ($key == 'encryption_key') {
            return $encryption_key;
        } elseif ($key == 'access_code') {
            return $access_code;
        }
    }

    /**
     * Retrieve gateway url
     *
     * @return string
     */
    protected function getCgiUrl() {
        return (bool) $this->getMethodConfigData('sandbox_flag') ? $this->getMethodConfigData('cgi_url_test_mode') : $this->getMethodConfigData('cgi_url');
    }

}
