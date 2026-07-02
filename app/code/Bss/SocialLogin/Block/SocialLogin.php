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
namespace Bss\SocialLogin\Block;

class SocialLogin extends \Magento\Framework\View\Element\Template
{
    protected $helper;

    protected $customerSession;

    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Bss\SocialLogin\Helper\Data $helper,
        \Magento\Customer\Model\Session $customerSession,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->helper = $helper;
        $this->customerSession = $customerSession;
    }
    public function isEnabledPopup()
    {
        if ($this->_request->getRouteName() !='customer' 
            && $this->_request->getControllerName() !='account' 
            && $this->helper->popupEnabled() 
            && $this->helper->checkLogin()) {
            return true;
        }
        return false;
    }
    public function isSecure()
    {
        return $this->helper->isSecure();
    }

    public function getLoginUrl()
    {
        return $this->getUrl('sociallogin/popup/login', ['_secure' => $this->isSecure()]);
    }

    public function getCreateUrl()
    {
        return $this->getUrl('sociallogin/popup/create', ['_secure' => $this->isSecure()]);
    }


    public function getForgotUrl()
    {
        return $this->getUrl('sociallogin/popup/forgot', ['_secure' => $this->isSecure()]);
    }

    public function recaptchaLogin()
    {
        return $this->helper->recaptcha('login');
    }

    public function recaptchaForgot()
    {
        return $this->helper->recaptcha('fogot-pasw');
    }

    public function recaptchaCreate()
    {
        return $this->helper->recaptcha('create');
    }
    
    public function getPreparedButtons()
    {
        return $this->helper->getPreparedButtons();
    }

    public function hasButtons()
    {
        return (bool)$this->helper->getPreparedButtons() && $this->helper->checkLogin();
    }

    public function showLimit()
    {
        return $this->helper->showLimit();
    }

    public function displayButtonLogin()
    {
       return $this->helper->displayButtonLogin();
    }

    public function displayButtonRegister()
    {
       return $this->helper->displayButtonRegister();
    }

    public function getSiteKey()
    {
        return $this->helper->getSiteKey();
    }

    public function getTheme()
    {
        return $this->helper->getTheme();
    }


    public function getType()
    {
        return $this->helper->getType();
    }


    public function getSize()
    {
        return $this->helper->getSize();
    }
}