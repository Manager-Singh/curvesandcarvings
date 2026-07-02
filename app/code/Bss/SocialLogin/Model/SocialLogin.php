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

class SocialLogin extends \Magento\Framework\Model\AbstractModel
{

    protected $type = null;

    protected $websiteId = null;

    protected $redirectUri = null;

    protected $accountData = [];

    protected $photoDir = null;

    protected $applicationId = null;

    protected $secret = null;

    protected $response_type = 'code';

    protected $dob = [];
    
    protected $gender = ['male', 'female'];


    public function _construct()
    {
        
        $this->_init('Bss\SocialLogin\Model\ResourceModel\SocialLogin');

        $this->helper = \Magento\Framework\App\ObjectManager::getInstance()->get('Bss\SocialLogin\Helper\Data');
        $this->websiteId = \Magento\Framework\App\ObjectManager::getInstance()->get('Magento\Store\Model\StoreManager')->getWebsite()->getId();
        $this->redirectUri = $this->helper->getCallbackURL($this->type);
        $this->photoDir = \Magento\Framework\App\ObjectManager::getInstance()->get('Magento\Framework\Filesystem')->getDirectoryRead(\Magento\Framework\App\Filesystem\DirectoryList::MEDIA)->getAbsolutePath('sociallogin'. DIRECTORY_SEPARATOR .'photo');
        $this->applicationId = trim((string)($this->helper->getConfig($this->helper->getConfigSectionId() .'/'. $this->type .'/client_id') ?? ''));

        $encryptor = \Magento\Framework\App\ObjectManager::getInstance()->get('Magento\Framework\Encryption\Encryptor');
        $this->secret = trim((string)($encryptor->decrypt($this->helper->getConfig($this->helper->getConfigSectionId() .'/'. $this->type .'/secret')) ?? ''));

    }

    public function enabled()
    {
        return (bool)$this->helper->getConfig($this->helper->getConfigSectionId() .'/'. $this->type .'/enable');
    }

    public function getResponseType()
    {
        return $this->response_type;
    }

    public function setAccountData($key, $value = null)
    {
        if(is_array($key)) {
            $this->accountData = array_merge($this->accountData, $key);
        }else{
            $this->accountData[$key] = $value;
        }
        return $this;
    }

    public function getAccountData($key = null)
    {
        if($key !== null) {
            return isset($this->accountData[$key]) ? $this->accountData[$key] : null;
        }
        return $this->accountData;
    }

    protected function _filterData($data)
    {
        $_data = [];
        foreach ($this->fields as $customerField => $userField) {
            $_data[$customerField] = ($userField && isset($data[$userField])) ? $data[$userField] : null;
        }

        $_data['firstname'] = $_data['firstname']?: '...';
        $_data['lastname'] = $_data['lastname']?: '...';
        
        if(empty($_data['email'])) {
            $_data['email'] = $this->_getRandomEmail();
        }

        if(!empty($_data['dob'])) {
            $_data['dob'] = call_user_func_array([$this, '_getDob'], array_merge([$_data['dob']], $this->dob));
        }else{
            $_data['dob'] = null;
        }

        if(!empty($_data['gender'])) {
            $options = \Magento\Framework\App\ObjectManager::getInstance()->get('Magento\Customer\Model\Customer')->getAttribute('gender')->getSource()->getAllOptions(false);

            switch($_data['gender']) {
                case $this->gender[0]: $_data['gender'] = $options[0]['value']; break;
                case $this->gender[1]: $_data['gender'] = $options[1]['value']; break;
                default: $_data['gender'] = 0;
            }
        }else{
            $_data['gender'] = 0;
        }

        $_data['taxvat'] = '0';

        $_data['password'] = $this->_getRandomPassword();

        return $_data;
    }

    protected function _getDob($date, $month = 'month', $day = 'day', $year = 'year', $separator = '/')
    {
        $date = explode($separator, $date);

        $result = [
            'year' => '0000',
            'month' => '00',
            'day' => '00'
        ];

        $result[$month] = $date[0];
        if(isset($date[1])) $result[$day] = $date[1];
        if(isset($date[2])) $result[$year] = $date[2];

        return implode('-', array_values($result));
    }

    protected function _getRandomEmail()
    {
        $len = 12;
        $chars = 'abcdefghijklmnopqrstuvwxyz0123456789';
        $address =  \Magento\Framework\App\ObjectManager::getInstance()->get('Magento\Framework\Math\Random')->getRandomString($len, $chars) .'@'. $this->type.'-user.com';
        return $address;
    }

    protected function _getRandomPassword()
    {
        $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()_-=+;:,.?";
        $password = substr( str_shuffle( $chars ), 0, 8 );

        return $password;
    }

    public function getButton()
    {
   
        $_url = null;
        $store = \Magento\Framework\App\ObjectManager::getInstance()->get('Magento\Store\Model\Store');
        $className = 'Bss\SocialLogin\Model\\'. ucfirst($this->type);
        $model = \Magento\Framework\App\ObjectManager::getInstance()->get($className);
        $store = \Magento\Framework\App\ObjectManager::getInstance()->get('Magento\Store\Model\Store');
        if($this->type == 'twitter' ) {
            $_url = $store->getUrl('sociallogin/account/login', ['type' => $this->type, 'refresh' => time()]);
        }else{
            $_url = $model->getButtonUrl();
        }

        $image = [];
        $media = $store->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA) .'sociallogin/';
        $small_icon = $this->helper->getConfig($this->helper->getConfigSectionId() .'/'. $this->type .'/small_icon');
        $image = $small_icon? $media . $small_icon : null;

        return [
            'href' => $_url,
            'type' => $this->type,
            'image' => $image,
            'login_text' => $this->helper->getConfig($this->helper->getConfigSectionId() .'/'. $this->type .'/login_btn_text'),
            'register_text' => $this->helper->getConfig($this->helper->getConfigSectionId() .'/'. $this->type .'/register_btn_text'),
            'popup_width' => $this->popupSize[0],
            'popup_height' => $this->popupSize[1],
        ];
    }

    public function getButtonUrl()
    {
        if(empty($this->applicationId) || empty($this->secret)) {
            $button_url = null;
        }else{
            $button_url = $this->authUrl;
        }
        return $button_url;
    }
    public function setCustomerIdByTokenId($customerId)
    {
        $data = [
            'type' => $this->type,
            'token_id' => $this->getAccountData('token_id'),
            'customer_id' => $customerId

        ];

        $fakemail = strpos($this->getAccountData('email'), $this->type.'-user.com');
        if ( $fakemail ) {
            $data['password_fake_email'] = $this->getAccountData('password');
        }
        $this->addData($data)->save();
        return $this;
    }

    public function getCustomerIdByTokenId()
    {
        $customerId = $this->_getCustomerIdByTokenId();
        if(!$customerId && $this->helper->isGlobalScope()) {
            $customerId = $this->_getCustomerIdByTokenId(true);
        }

        return $customerId;
    }

    protected function _getCustomerIdByTokenId($useGlobalScope = false)
    {
        $customerId = 0;

        if($this->getAccountData('token_id')) {
            $collection = $this->getCollection()
                ->join(['ce' => 'customer_entity'], 'ce.entity_id = main_table.customer_id', null)
                ->addFieldToFilter('main_table.type', $this->type)
                ->addFieldToFilter('main_table.token_id', $this->getAccountData('token_id'))
                ->setPageSize(1);

            if($useGlobalScope == false) {
                $collection->addFieldToFilter('ce.website_id', $this->websiteId);
            }

            $customerId = $collection->getFirstItem()->getData('customer_id');
        }

        return $customerId;
    }

    public function getCustomerIdByEmail()
    {
        $customerId = $this->_getCustomerIdByEmail();
        if(!$customerId && $this->helper->isGlobalScope()) {
            $customerId = $this->_getCustomerIdByEmail(true);
        }
        return $customerId;
    }

    protected function _getCustomerIdByEmail($useGlobalScope = false)
    {
        $customerId = 0;

        if(is_string($this->getAccountData('email'))) {
            $collection = \Magento\Framework\App\ObjectManager::getInstance()->get('Magento\Customer\Model\Customer')->getCollection()
                ->addFieldToFilter('email', $this->getAccountData('email'))
                ->setPageSize(1);

            if($useGlobalScope == false) {
                $collection->addFieldToFilter('website_id', $this->websiteId);
            }

            $customerId = $collection->getFirstItem()->getId();
        }

        return $customerId;
    }

    public function registerAccount()
    {
        $customerId = 0;
        $errors = [];
        $customer = \Magento\Framework\App\ObjectManager::getInstance()->get('Magento\Customer\Model\Customer')->setId(null);
        $storeId = \Magento\Framework\App\ObjectManager::getInstance()->get('Magento\Store\Model\StoreManager')->getStore()->getId();

        try{
            $customer->setData($this->getAccountData())
                        ->setPassword($this->getAccountData('password'))
                        ->setPasswordConfirmation($this->getAccountData('password'))
                        ->setData('is_active', 1)
                        ->getGroupId();
            if($this->helper->showPassword()){
                $customer->setData('showPassword', 1);
            }    
                $customerId = $customer->save()->getId();
                $customer->setConfirmation(null)->save();

            if (strpos($this->getAccountData('email'), $this->type.'-user.com') === false) {
                $customer->sendNewAccountEmail('registered', '', $storeId);
            }
        } catch (\Exception $e) {
            $errors[] = $e->getMessage();
        }

        $this->setCustomer($customer);
        $this->setErrors($errors);

        return $customerId;
    }

    public function setCustomerPhoto($customerId)
    {
        $upload = false;

        $fileUrl = $this->getAccountData('photo');
        if(empty($fileUrl) || !is_numeric($customerId) || $customerId < 1) {
            return;
        }

        $tmpPath = $this->photoDir . DIRECTORY_SEPARATOR . $customerId .'.tmp';
        $io = \Magento\Framework\App\ObjectManager::getInstance()->get('Magento\Framework\Filesystem\Io\File');

        try{
            $io->mkdir($this->photoDir);
            if($file = $this->_loadFile($fileUrl)) {
                if(file_put_contents($tmpPath, $file) > 0) {

                    $image = \Magento\Framework\App\ObjectManager::getInstance()->create('Magento\Framework\Image', ['fileName' => $tmpPath]);
                    $image->resize(40);

                    $fileName = $customerId .'.png';
                    $image->save(null, $fileName);

                    $upload = true;
                }
            }
        }catch(\Exception $e) {}

        if($io->fileExists($tmpPath)) {
            $io->rm($tmpPath);
        }

        return $upload;
    }

    protected function _loadFile($url, $count = 1) {

        if ($count > 5) {
            return false;
        }
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $data = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if (!$data) {
            return false;
        }

        $dataArray = explode("\r\n\r\n", $data, 2);
        if (count($dataArray) != 2) {
            return false;
        }

        list($header, $body) = $dataArray;
        if ($httpCode == 301 || $httpCode == 302) {
            $matches = [];
            preg_match('/Location:(.*?)\n/', $header, $matches);

            if (isset($matches[1])) {
                return $this->_loadFile(trim($matches[1]), $count++);
            }
        } else {
            return $body;
        }
    }

    protected function _httpRequest($url, $method = 'GET', $params = [] , $type = '')
    {
        $client = new \Zend_Http_Client($url, ['timeout' => 60]);

        switch ($method) {
            case 'GET':
                $client->setParameterGet($params);
                break;
            case 'POST':
                $client->setParameterPost($params);
                break;
            case 'DELETE':
                break;
            default:
                throw new \Magento\Framework\Validator\Exception(__('Required HTTP method is not supported.')
                );
        }

        $response = $client->request($method);
        $decodedResponse = json_decode($response->getBody());
        
        if (isset($decodedResponse->error)) {
            throw new \Magento\Framework\Validator\Exception(__($decodedResponse->error->message));
        }

        if($response->isError()) {
            $status = $response->getStatus();
            if(($status == 400 || $status == 401)) {
                if(isset($decodedResponse->error->message)) {
                    $message = $decodedResponse->error->message;
                } else {
                    $message = __('Unspecified OAuth error occurred.');
                }
                throw new \Magento\Framework\Validator\Exception(__($message));
            } else {
                $message = sprintf('HTTP error %d occurred while issuing request.',$status);
                throw new \Magento\Framework\Validator\Exception(__($message));
            }
        }

        if(empty($decodedResponse) && ($type == 'facebook' || $type == 'vkontakte' || $type == 'pinterest')) {
            $parsed_response = [];
            parse_str((string)($response->getBody() ?? ''), $parsed_response);
            $decodedResponse = json_decode(json_encode($parsed_response));
        }
        return $decodedResponse;
    }
    
}