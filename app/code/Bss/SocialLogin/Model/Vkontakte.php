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

class Vkontakte extends \Bss\SocialLogin\Model\SocialLogin
{
    const OAUTH_AUTH_URI = 'https://oauth.vk.com/authorize';

    protected $type = 'vkontakte';

    protected $fields = [
                    'user_id' => 'uid',
                    'firstname' => 'first_name',
                    'lastname' => 'last_name',
                    'email' => 'email',
                    'dob' => null,
                    'gender' => 'sex',
                    'photo' => 'photo',
                ];

    protected $authUrl = [
                    'scope' => 'email',
                    'response_type' => 'token'
                ];

    protected $popupSize = [650, 350];

    public function _construct()
    {      
        parent::_construct();
        
    }
    public function getButtonUrl()
    {
        $this->authUrl = $this->createAuthUrl();
        return parent::getButtonUrl();
    }

    public function loadAccountInfo($response)
    {
        if(empty($response)) {
            return false;
        }
        
        $data = [];

        $params = [
            'client_id' => $this->applicationId,
            'client_secret' => $this->secret,
            'code' => $response,
            'redirect_uri' => $this->redirectUri
        ];
    
        $token = null;
        $token = $this->_httpRequest('https://oauth.vk.com/access_token','POST', $params,$this->type);

        if (isset($token->access_token)) {

            $params = [
                'access_token'  => $token->access_token,
                'fields'        => implode(',', $this->fields)
            ];
            
            if($data = $this->_httpRequest('https://api.vk.com/method//users.get','GET', $params, $this->type)) {
                $data = json_decode(json_encode($data), true);
            }
            $data = $data['response'][0];
            $data['sex'] = ($data['sex'] == 0 || $data['sex'] == 2) ? 1 : 0;
            $data['email'] = $token->email;
            
        }

        if(!$this->accountData = $this->_filterData($data)) {
            return false;
        }

        return true;
    }

    public function createAuthUrl()
    {
        $url =
        self::OAUTH_AUTH_URI.'?'.
            http_build_query(
                [
                    'client_id'     => $this->applicationId,
                    'redirect_uri'  => $this->redirectUri,
                    'response_type' => $this->response_type,
                    'scope' => 'email'
                ]
            );

        return $url;

    }
    protected function _filterData($data)
    {
        if(empty($data['uid'])) {
            return false;
        }

        return parent::_filterData($data);
    }

}