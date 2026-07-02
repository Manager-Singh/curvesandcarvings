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

class Live extends \Bss\SocialLogin\Model\SocialLogin
{
    const OAUTH2_TOKEN_URI = 'https://login.live.com/oauth20_token.srf';

    const OAUTH2_AUTH_URI = 'https://login.live.com/oauth20_authorize.srf';

    const OAUTH2_SERVICE_URI = 'https://apis.live.net/v5.0';

    protected $type = 'live';

    protected $scope = [
        'wl.signin', 
        'wl.basic', 
        'wl.emails'
    ];

	protected $fields = [
					'token_id' => 'id',
		            'firstname' => 'first_name',
		            'lastname' => 'last_name',
		            'email' => 'email',
		            'dob' => null,
                    'gender' => null,
                    'photo' => 'picture',
				];

	protected $authUrl = null;

    protected $popupSize = [630, 650];

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
     $token = $this->_httpRequest(
            self::OAUTH2_TOKEN_URI,
            'POST',
            [
                'code' => $response,
                'redirect_uri' => $this->redirectUri,
                'client_id' => $this->applicationId,
                'client_secret' => $this->secret,
                'grant_type' => 'authorization_code'
            ]
        ); 

         $url = self::OAUTH2_SERVICE_URI.'/me';

        $params = [];
        $params = array_merge([
            'access_token' => $token->access_token
        ], $params);

        $data = [];

        $responses = $this->_httpRequest($url, 'GET', $params);
        $data = json_decode(json_encode($responses), true);

        $data['email'] = $data['emails']['account'];

        if(!$this->accountData = $this->_filterData($data)) {
        	return false;
        }

        return true;
    }
    
    public function createAuthUrl()
    {
        $url =
        self::OAUTH2_AUTH_URI.'?'.
            http_build_query(
                [
                    'response_type' => 'code',
                    'redirect_uri' => $this->redirectUri,
                    'client_id' => $this->applicationId,
                    'scope' => implode(' ', $this->scope),
                ]
            );

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