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

class Twitter extends \Bss\SocialLogin\Model\SocialLogin
{
    const URL_AUTHORIZE = 'https://api.twitter.com/oauth/authorize';

    const OAUTH_URI = 'https://api.twitter.com/oauth';

    const OAUTH2_SERVICE_URI = 'https://api.twitter.com/1.1';

    protected $type = 'twitter';

    protected $response_type = ['oauth_token', 'oauth_verifier'];

    protected $token = null;

    protected $fields = [
                    'token_id' => 'id',
                    'firstname' => 'firstname',
                    'lastname' => 'lastname',
                    'email' => null,
                    'dob' => null,
                    'gender' => null,
                    'photo' => 'profile_image_url',
                ];

    protected $authUrl = null;

    protected $popupSize = [630, 650];

    protected $sessiont;

    public function _construct()
    {      
        $this->sessiont =  \Magento\Framework\App\ObjectManager::getInstance()->get('\Magento\Customer\Model\Session');
        parent::_construct();
    }

    public function getButtonUrl()
    {
        $token = $this->createAuthUrl();
        if(!empty($token)) {
            $this->authUrl = self::URL_AUTHORIZE .'?oauth_token='. $token;
        }
        return parent::getButtonUrl();
    }

    public function loadAccountInfo($response)
    {
        $client = new \Zend_Oauth_Consumer(
                [
                    'callbackUrl' => $this->redirectUri,
                    'siteUrl' => self::OAUTH_URI,
                    'authorizeUrl' => self::OAUTH_URI.'/authenticate',
                    'consumerKey' => $this->applicationId,
                    'consumerSecret' => $this->secret
                ]
            );
        
        if (!empty($response['oauth_token']) && $requesttk = $this->sessiont->getRequestToken()) {
   
            $this->token = $client->getAccessToken(
                    $response,
                    unserialize($requesttk)
                    );
            
            $url = self::OAUTH2_SERVICE_URI.'/account/verify_credentials.json'; 
            $params = ['skip_status' => true];

            if($data = $this->_httpRequest($url, 'GET', $params)) {
                $data = json_decode(json_encode($data), true);
            }

            if(!$this->accountData = $this->_filterData($data)) {

                return false;
            }
            
        }

        return true;
    }

    protected function _httpRequest($url, $method = 'GET', $params = [], $type = '')
    {
        $client = $this->token->getHttpClient(
            [
                'callbackUrl' => $this->redirectUri,
                'siteUrl' => self::OAUTH_URI,
                'consumerKey' => $this->applicationId,
                'consumerSecret' => $this->secret
            ]
        );

        $client->setUri($url);

        switch ($method) {
            case 'GET':
                $client->setMethod(\Zend_Http_Client::GET);
                $client->setParameterGet($params);
                break;
            case 'POST':
                $client->setMethod(\Zend_Http_Client::POST);
                $client->setParameterPost($params);
                break;
            case 'DELETE':
                $client->setMethod(\Zend_Http_Client::DELETE);
                break;
            default:
               throw new \Magento\Framework\Validator\Exception(__('Required HTTP method is not supported.'));
        }

        $response = $client->request();


        $decoded_response = json_decode($response->getBody());

        if($response->isError()) {
            $status = $response->getStatus();
            if(($status == 400 || $status == 401 || $status == 429)) {
                if(isset($decoded_response->error->message)) {
                    $message = $decoded_response->error->message;
                } else {
                    $message = __('Unspecified OAuth error occurred.');
                }
                throw new \Magento\Framework\Validator\Exception(__($message));
            } else {
                $message = sprintf('HTTP error %d occurred while issuing request.',$status);
                throw new \Magento\Framework\Validator\Exception(__($message));
            }
        }

        return $decoded_response;
    }

    protected function createAuthUrl()
    {
        $config = [
            'callbackUrl'=>$this->redirectUri,
            'siteUrl' => 'https://api.twitter.com/oauth',
            'consumerKey'=>$this->applicationId,
            'consumerSecret'=>$this->secret
        ];
        $oauth= new \Zend_Oauth_Consumer($config);

        try{
            $request_token = $oauth->getRequestToken();
        }
        catch(\Exception $e)
        {
            // throw new \Magento\Framework\Validator\Exception($e->getMessage());
        }

        $this->sessiont->setRequestToken(serialize($request_token));

        $exploded_request_token = explode('=',str_replace('&','=',$request_token));

        $oauth_token = $exploded_request_token[1];

        return $oauth_token;
    }

    protected function _filterData($data)
    {
        if(empty($data['id'])) {
            return false;
        }

        if(!empty($data['name'])) {
            $nameParts = explode(' ', $data['name'], 2);
            $data['firstname'] = $nameParts[0];
            $data['lastname'] = !empty($nameParts[1])? $nameParts[1] : '';
        }

        return parent::_filterData($data);
    }

}