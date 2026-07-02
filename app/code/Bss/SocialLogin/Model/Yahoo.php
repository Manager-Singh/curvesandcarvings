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

class Yahoo extends \Bss\SocialLogin\Model\SocialLogin
{
    const OAUTH2_TOKEN_URI = 'https://api.login.yahoo.com/oauth2/get_token';

    const OAUTH2_AUTH_URI = 'https://api.login.yahoo.com/oauth2/request_auth';

    const OAUTH2_SERVICE_URI = 'https://social.yahooapis.com/v1';

    protected $type = 'yahoo';

    protected $scope = [];

    protected $state = '';

	protected $fields = [
					'token_id' => 'id',
		            'firstname' => 'givenName',
		            'lastname' => 'familyName',
		            'email' => 'email',
		            'dob' => 'birthdate',
                    'gender' => 'gender',
                    'photo' => 'image',
				];

	protected $authUrl = null;

    protected $token = null;

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
                    'grant_type' => 'authorization_code',
                ]
        ); 

        $url = self::OAUTH2_SERVICE_URI.'/user/'.$token->xoauth_yahoo_guid.'/profile?format=json';

        $params = [];
        $params = array_merge([
            'access_token' => $token->access_token
        ], $params);

        $data = [];

        $data = $this->_httpRequest($url, 'GET', $params);

        $data = json_decode(json_encode($data), true);

        $data['id'] = $data['profile']['guid'];
        $data['givenName'] = $data['profile']['givenName'];
        $data['familyName'] = $data['profile']['familyName'];
        $data['birthdate'] = (isset($data['profile']['birthdate'])) ? $data['profile']['birthdate'] :'';
        $data['gender'] = ($data['profile']['gender'] == 'F') ? 1 : 0;
        $data['email'] = $data['profile']['emails'][0]['handle'];
        $data['image'] = $data['profile']['image']['imageUrl']; 
        unset($data['profile']);

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