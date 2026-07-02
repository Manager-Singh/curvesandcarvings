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

class Facebook extends \Bss\SocialLogin\Model\SocialLogin
{
    const OAUTH_AUTH_URI = 'https://www.facebook.com/dialog/oauth';

	protected $type = 'facebook';

	protected $fields = [
					'token_id' => 'id',
		            'firstname' => 'first_name',
		            'lastname' => 'last_name',
		            'email' => 'email',
		            'dob' => 'birthday',
                    'gender' => 'gender',
                    'photo' => 'picture',
				];

	protected $authUrl = [
					'scope' => 'email,user_birthday',
                    'display' => 'popup',
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
        $token = $this->_httpRequest('https://graph.facebook.com/oauth/access_token','POST', $params,$this->type);

        if (isset($token->access_token)) {
            $params = [
                'access_token'  => $token->access_token,
                'fields'        => implode(',', $this->fields)
            ];
    
            if($data = $this->_httpRequest('https://graph.facebook.com/me','GET', $params , $this->type)) {
                $data = json_decode(json_encode($data), true);
            }

            if(!empty($data['id'])) {
                $data['picture'] = 'https://graph.facebook.com/'. $data['id'] .'/picture?return_ssl_resources=true';
            }
            
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
            urldecode(http_build_query(
                [
                    'client_id'     => $this->applicationId,
                    'redirect_uri'  => $this->redirectUri,
                    'response_type' => $this->response_type,
                    'display'       => 'popup'
                ]
            ));

        return $url;

    }

    protected function _filterData($data)
    {
    	if(empty($data['id'])) {
    		return false;
    	}
        return parent::_filterData($data);
    }

}