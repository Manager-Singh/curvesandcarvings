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

class Linkedin extends \Bss\SocialLogin\Model\SocialLogin
{

    const OAUTH2_TOKEN_URI = 'https://www.linkedin.com/uas/oauth2/accessToken';

    const OAUTH2_AUTH_URI = 'https://www.linkedin.com/uas/oauth2/authorization';

    const OAUTH2_SERVICE_URI = 'https://api.linkedin.com/v1';
    
    protected $type = 'linkedin';

    protected $scope = [
        'r_basicprofile',
        'r_emailaddress'
    ];

    protected $state = '987654321';

	protected $fields = [
					'token_id' => 'id',
		            'firstname' => 'firstName',
		            'lastname' => 'lastName',
		            'email' => 'emailAddress',
		            'dob' => null,
                    'gender' => null,
                    'photo' => null,
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

        $userInfoApi = [
                        'id',
                        'first-name',
                        'last-name',
                        'headline',
                        'picture-url',
                        'email-address',
                        'phone-numbers',
                        'location'
                    ];
                    
        $url = self::OAUTH2_SERVICE_URI.'/people/~:('.implode(',', $userInfoApi).')?format=json';

        $params = [];
        $params = array_merge([
            'oauth2_access_token' => $token->access_token
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
        $this->state = md5(uniqid(rand(), true));
        $url =
        self::OAUTH2_AUTH_URI.'?'.
            http_build_query(
                [
                    'response_type' => 'code',
                    'redirect_uri' => $this->redirectUri,
                    'client_id' => $this->applicationId,
                    'scope' => implode(' ', $this->scope),
                    'state' => $this->state,
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