<?php
/**
 * BSS Commerce Co.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the EULA
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://bsscommerce.com/Bss-Commerce-License.txt
 *
 * @category   BSS
 * @package    Bss_AjaxSocialLogin
 * @author     Extension Team
 * @copyright  Copyright (c) 2017-2018 BSS Commerce Co. ( http://bsscommerce.com )
 * @license    http://bsscommerce.com/Bss-Commerce-License.txt
 */
namespace Bss\SocialLogin\Model;

class Recaptcha
{

    const REQUEST_URL = 'https://www.google.com/recaptcha/api/siteverify';

    protected $helper;

    protected $remoteip;
    
    protected $client;

    public function __construct(
        \Bss\SocialLogin\Helper\Data $helper,
        \Magento\Framework\HTTP\PhpEnvironment\RemoteAddress $remoteip
    ) {
        $this->helper = $helper;
        $this->remoteip = $remoteip;
    }

    public function verify($recaptcha_response)
    {
        $params = [
            'secret'   => $this->helper->getSecretKey(),
            'response' => $recaptcha_response,
            'remoteip' => $this->remoteip->getRemoteAddress()
        ];
        
        $client = $this->getHttpClient();
        $client->setParameterPost($params);
        $errors = '';

        try {
            $response = $client->request('POST');
            $data = json_decode($response->getBody());
            if (array_key_exists('error-codes', $data)) {
                $errors = $data['error-codes'];
            }
        } catch (\Exception $e) {
            $data = ['success' => false];
        }

        return $errors;
    }
    public function setHttpClient(Varien_Http_Client $client)
    {
        $this->client = $client;
        
        return $this;
    }

    public function getHttpClient()
    {
        if (is_null($this->client)) {
            $this->client = new \Zend_Http_Client();
        }
        
        $this->client->setUri(self::REQUEST_URL);

        return $this->client;
    }
    
}