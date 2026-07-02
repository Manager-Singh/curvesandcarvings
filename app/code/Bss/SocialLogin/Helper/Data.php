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
namespace Bss\SocialLogin\Helper;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    const XML_PATH_SECURE_IN_FRONTEND = 'web/secure/use_in_frontend';

    protected $helperbackend;

    protected $customer;

    protected $customerSession;

    protected $storeManager;

    protected $httpcontext;

    protected $filesystem;

    protected $urlInterface;

    protected $customerurl;

    protected $buttons = null;
    
    public function __construct(
        \Magento\Backend\Helper\Data $helperbackend,
        \Magento\Customer\Model\Customer $customer,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\App\Http\Context $httpcontext,
        \Magento\Framework\Filesystem $filesystem,
        \Magento\Framework\UrlInterface $urlInterface,
        \Magento\Customer\Model\Url $customerurl,
        \Magento\Framework\App\Helper\Context $context
    ) {
        $this->helperbackend = $helperbackend;
        $this->customer = $customer;
        $this->customerSession = $customerSession;
        $this->storeManager = $storeManager;
        $this->httpcontext = $httpcontext;
        $this->filesystem = $filesystem;
        $this->urlInterface = $urlInterface;
        $this->customerurl = $customerurl;
        parent::__construct($context);
    }

    public function getConfig($path, $store = null, $scope = null)
    {
        if ($scope === null) {
            $scope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
        }
        return $this->scopeConfig->getValue($path, $scope, $store);
    }
    public function getConfigSectionId()
    {
        return 'bss_sociallogin';
    }

    public function moduleEnabled()
    {
        return (bool)$this->getConfig('bss_sociallogin/general/enable');
    }
    public function popupEnabled()
    {
        return (bool)$this->getConfig('bss_sociallogin/general/popup_ajax');
    }
    public function isSecure()
    {
        return (bool)$this->getConfig(self::XML_PATH_SECURE_IN_FRONTEND);
    }

    public function displayPopup(){
        return $this->getConfig('bss_sociallogin/button_social/display_at_popup');
    }

    public function displayButtonLogin(){
       return explode(',',$this->getConfig('bss_sociallogin/button_social/login_byclass'));
    }

    public function displayButtonRegister(){
       return explode(',',$this->getConfig('bss_sociallogin/button_social/register_byclass'));
    }

    public function showLimit()
    {
        return $this->getConfig('bss_sociallogin/button_social/show_limit');
    }

    public function showPassword(){
        
        return $this->moduleEnabled() && $this->getConfig('bss_sociallogin/general/send_password');
    }

    public function photoEnabled()
    {
        return $this->moduleEnabled() && $this->getConfig('bss_sociallogin/general/enable_photo');
    }

    public function recaptcha($path)
    {
        $recaptcha = explode(',', $this->getConfig('bss_sociallogin/recaptcha/form_apply'));
        return in_array($path,$recaptcha);
    }

    public function getSiteKey()
    {
        return  $this->getConfig('bss_sociallogin/recaptcha/site_key');
    }
    
    public function getSecretKey()
    {
        return  $this->getConfig('bss_sociallogin/recaptcha/secret_key');
    }

    public function getTheme()
    {
        return $this->getConfig('bss_sociallogin/recaptcha/theme');
    }

    public function getType()
    {
        return $this->getConfig('bss_sociallogin/recaptcha/type');
    }

    public function getSize()
    {
        return $this->getConfig('bss_sociallogin/recaptcha/size');
    }

    public function checkLogin()
    {
        if( $this->httpcontext->getValue(\Magento\Customer\Model\Context::CONTEXT_AUTH) ){
            return false;
        }
        
        if( $this->customerSession->isLoggedIn()) {
            return false;
        }

        return true;
    }

    public function getPhotoPath($checkIsEnabled = true)
    {
        if($checkIsEnabled && !$this->photoEnabled()) {
            return false;
        }

        if(!$this->customerSession->isLoggedIn()) {
            return false;
        }
        if(!$customerId =$this->customerSession->getCustomerId()) {
            return false;
        }
        $directoryRead = $this->filesystem->getDirectoryRead(\Magento\Framework\App\Filesystem\DirectoryList::MEDIA);
        $path = 'bss_sociallogin'. DIRECTORY_SEPARATOR .'photo'. DIRECTORY_SEPARATOR . $customerId .'.png';
        $pathUrl = $this->storeManager->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA) .'bss_sociallogin/photo/' . $customerId .'.png';

        if(!$directoryRead->isExist($path)) {
            return false;
        }

        return $pathUrl;
    }

    public function isGlobalScope()
    {
        return $this->customer->getSharingConfig()->isGlobalScope();
    }

    public function getCallbackURL($provider, $byRequest = false)
    {
        $request = $this->_getRequest();
        $websiteCode = $request->getParam('website');
        $defaultStoreId = $this->storeManager
            ->getWebsite( $byRequest? $websiteCode : null )
            ->getDefaultGroup()
            ->getDefaultStoreId();

        if(!$defaultStoreId) {
            $websites = $this->storeManager->getWebsites(true);
            foreach($websites as $website) {
                $defaultStoreId = $website
                    ->getDefaultGroup()
                    ->getDefaultStoreId();

                if ($defaultStoreId) {
                    break;
                }
            }
        }

        if(!$defaultStoreId) {
            $defaultStoreId = 1;
        }

        $url = $this->storeManager->getStore($defaultStoreId)->getUrl('sociallogin/account/login', ['type' => $provider, 'key' => null, '_nosid' => true]);

        $url = str_replace($this->helperbackend->getAreaFrontName() . '/','',$url);

        if(false !== ($length = stripos($url, '?'))) {
            $url = substr($url, 0, $length);
        }

        if($byRequest) {

            if($this->getConfig('web/seo/use_rewrites')) {
                $url = str_replace('index.php/', '', $url);
            }
        }

        return $url;
    }

    public function getTypes($onlyEnabled = true)
    {
        $groups = $this->getConfig('bss_sociallogin');
        unset($groups['general']);
        unset($groups['recaptcha']);
        unset($groups['button_social']);
        $types = [];
        if (!empty($groups)) {
            foreach ($groups as $name => $fields) {
                if($onlyEnabled && empty($fields['enable'])) {
                    continue;
                }
                $types[] = $name;
            }
        }
        return $types;
    }

    public function getButtons()
    {
        if (null === $this->buttons) {
            $types = $this->getTypes(false);

            $this->buttons = [];
            foreach ($types as $type) {
                $type = \Magento\Framework\App\ObjectManager::getInstance()->get('Bss\SocialLogin\Model\\'. ucfirst($type));

                if($type->enabled()) {
                    $button = $type->getButton();
                    $this->buttons[$button['type']] = $button;
                }
            }
        }
        return $this->buttons;
    }

    public function getPreparedButtons()
    {
            $buttonsPrepared = [];
            $buttons = $this->getButtons();

            $storeName = $this->_getRequest()->getParam('store');
            $sortableString = $this->getConfig('bss_sociallogin/button_social/sort_bss_sociallogin', $storeName);
            $sortable = null;
            parse_str((string)($sortableString ?? ''), $sortable);

            if(is_array($sortable)) {
                if (isset($sortable['sort'])) {
                    foreach ($sortable['sort'] as  $button) {
                        if(isset($buttons[$button])) {
                            $buttonsPrepared[$button] = $buttons[$button];
                            unset($buttons[$button]);
                        }
                    }
                }

            }
        return array_merge($buttonsPrepared,$buttons);
    }

    public function getRedirectUrl($path = 'login')
    {
        $redirectUrl = $this->getConfig('bss_sociallogin/general/redirect_for_'.$path);
        if ($redirectUrl == 'custom') {
            $redirectUrl = $this->getConfig('bss_sociallogin/general/redirect_for_'.$path.'_link');
        }

        if ($redirectUrl == 'url_dashboard') {
            $redirectUrl = $this->customerurl->getDashboardUrl();
        }
        if ($redirectUrl == 'url_shoppingcart') {
            $redirectUrl = $this->urlInterface->getUrl('checkout/cart');
        }
        if ($redirectUrl == 'url_checkoutpage') {
            $redirectUrl = $this->urlInterface->getUrl('checkout');
        }
        return $redirectUrl;
    }
}