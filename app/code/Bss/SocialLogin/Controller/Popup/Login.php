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
namespace Bss\SocialLogin\Controller\Popup;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Bss\SocialLogin\Helper\Data as HelperData;
use Magento\Customer\Api\AccountManagementInterface;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\Json\Helper\Data as JsonHelper;

class Login extends Action
{
    protected $helperData;

    protected $accountManagement;

    protected $customerSession;

    protected $jsonHelper;

    public function __construct(
        Context $context,
        HelperData $helperData,
        AccountManagementInterface $accountManagement,
        CustomerSession $customerSession,
        JsonHelper $jsonHelper
    ) {
        parent::__construct($context);
        $this->helperData        = $helperData;
        $this->accountManagement = $accountManagement;
        $this->customerSession   = $customerSession;
        $this->jsonHelper        = $jsonHelper;
    }

    public function execute()
    {
        $username      = $this->getRequest()->getParam('username', false);
        $password      = $this->getRequest()->getParam('password', false);
        $recaptcha_response = $this->getRequest()->getParam('g-recaptcha-response', false);
        $result = [];
        $captchaError = '';
        if ($recaptcha_response) {
            $model = \Magento\Framework\App\ObjectManager::getInstance()->get('Bss\SocialLogin\Model\Recaptcha');
            $captchaError = $model->verify($recaptcha_response);
        }
        if ( !empty($captchaError) ) {
            $result['error'] = $captchaError;
            $this->getResponse()->setBody($this->jsonHelper->jsonEncode($result));
        } elseif ($username && $password) {

            try {
                $accountManage = $this->accountManagement;
                $customer      = $accountManage->authenticate(
                    $username,
                    $password
                );
                $this->customerSession->setCustomerDataAsLoggedIn($customer);
                $this->customerSession->regenerateId();
            } catch (\Exception $e) {
                $result['error']   = true;
                $result['message'] = $e->getMessage();
            }
            
            if (!isset($result['error'])) {
                $result['success'] = true;
                $result['message'] = __('Login successfully. Please wait ...');
                $result['redirect'] = $this->helperData->getRedirectUrl();
            }
        } else {
            $result['error']   = true;
            $result['message'] = __(
                'Please enter a username and password.');
        }
        
        $this->getResponse()->setBody($this->jsonHelper->jsonEncode($result));
        return;
    }
}
