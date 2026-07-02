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

class Googleplus extends \Bss\SocialLogin\Model\SocialLogin
{
    const OAUTH2_REVOKE_URI = 'https://accounts.google.com/o/oauth2/revoke';

    const OAUTH2_TOKEN_URI = 'https://accounts.google.com/o/oauth2/token';

    const OAUTH2_AUTH_URI = 'https://accounts.google.com/o/oauth2/auth';

    const OAUTH2_SERVICE_URI = 'https://www.googleapis.com/oauth2/v2';
    
    protected $type = 'googleplus';

    protected $scope = [

        'https://www.googleapis.com/auth/userinfo.profile',

        'https://www.googleapis.com/auth/userinfo.email',

    ];

    protected $state = '';

    protected $access = 'offline';

    protected $prompt = 'auto';

	protected $fields = [
					'token_id' => 'id',
		            'firstname' => 'given_name',
		            'lastname' => 'family_name',
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

        $url = self::OAUTH2_SERVICE_URI.'/userinfo';

        $params = [];
        $params = array_merge([
            'access_token' => $token->access_token

        ], $params);

        $data = [];

        $responses = $this->_httpRequest($url, 'GET', $params);
        $data = json_decode(json_encode($responses), true);


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
                    'state' => $this->state,
                    'access_type' => $this->access,
                    'approval_prompt' => $this->prompt
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