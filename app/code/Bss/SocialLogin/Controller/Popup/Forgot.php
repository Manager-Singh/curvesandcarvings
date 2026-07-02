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

use Magento\Customer\Api\AccountManagementInterface;
use Magento\Customer\Model\AccountManagement;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Escaper;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Customer\Controller\AbstractAccount;
use Magento\Framework\Json\Helper\Data as JsonHelper;

class Forgot extends Action
{
    protected $customerAccountManagement;

    protected $escaper;

    protected $session;

    protected $jsonHelper;

    public function __construct(
        Context $context,
        Session $customerSession,
        AccountManagementInterface $customerAccountManagement,
        Escaper $escaper,
        JsonHelper $jsonHelper
    ) {
        $this->session                   = $customerSession;
        $this->customerAccountManagement = $customerAccountManagement;
        $this->escaper                   = $escaper;
        $this->jsonHelper                = $jsonHelper;
        parent::__construct($context);
    }

    public function execute()
    {
        $result  = [];
        $email = (string)$this->getRequest()->getPost('email');
        $recaptcha_response = $this->getRequest()->getParam('g-recaptcha-response', false);
        $captchaError = '';
        if ($recaptcha_response) {
            $model = \Magento\Framework\App\ObjectManager::getInstance()->get('Bss\SocialLogin\Model\Recaptcha');
            $captchaError = $model->verify($recaptcha_response);
        }

        if (!empty($captchaError)) {
            $result['error'] = $captchaError;
        } elseif ($email) {
            if (!\Zend_Validate::is($email, 'EmailAddress')) {
                $this->session->setForgottenEmail($email);
                $result['error']     = true;
                $result['message'][] = __('Please correct the email address.');
            }

            try {
                $this->customerAccountManagement->initiatePasswordReset(
                    $email,
                    AccountManagement::EMAIL_RESET
                );
                $result['success']   = true;
                $result['message'][] = __(
                    'If there is an account associated with %1 you will receive an email with a link to reset your password.',
                    $this->escaper->escapeHtml($email)
                );
            } catch (NoSuchEntityException $e) {
                // Do nothing, we don't want anyone to use this action to determine which email accounts are registered.
            } catch (\Exception $exception) {
                $result['error']     = true;
                $result['message'][] = __('We\'re unable to send the password reset email.');
            }

        }

        $this->getResponse()->setBody($this->jsonHelper->jsonEncode($result));
        return;
    }
}
