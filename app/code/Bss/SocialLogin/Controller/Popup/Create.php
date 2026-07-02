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

use Magento\Customer\Model\Account\Redirect as AccountRedirect;
use Magento\Customer\Api\Data\AddressInterface;
use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\App\Action\Context;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Customer\Api\AccountManagementInterface;
use Magento\Customer\Helper\Address;
use Magento\Framework\UrlFactory;
use Magento\Customer\Model\Metadata\FormFactory;
use Magento\Newsletter\Model\SubscriberFactory;
use Magento\Customer\Api\Data\RegionInterfaceFactory;
use Magento\Customer\Api\Data\AddressInterfaceFactory;
use Magento\Customer\Api\Data\CustomerInterfaceFactory;
use Magento\Customer\Model\Url as CustomerUrl;
use Magento\Customer\Model\Registration;
use Magento\Framework\Escaper;
use Magento\Customer\Model\CustomerExtractor;
use Magento\Framework\Exception\StateException;
use Magento\Framework\Exception\InputException;
use Magento\Customer\Controller\AbstractAccount;
use Magento\Framework\Json\Helper\Data as JsonHelper;
use Bss\SocialLogin\Helper\Data as HelperData;

class Create extends AbstractAccount
{
    protected $accountManagement;

    protected $addressHelper;

    protected $formFactory;

    protected $subscriberFactory;

    protected $regionDataFactory;

    protected $addressDataFactory;

    protected $registration;

    protected $customerDataFactory;

    protected $customerUrl;

    protected $escaper;

    protected $customerExtractor;

    protected $urlFactory;

    protected $dataObjectHelper;

    protected $session;

    private $accountRedirect;

    protected $jsonHelper;

    public function __construct(
        Context $context,
        Session $customerSession,
        ScopeConfigInterface $scopeConfig,
        StoreManagerInterface $storeManager,
        AccountManagementInterface $accountManagement,
        Address $addressHelper,
        UrlFactory $urlFactory,
        FormFactory $formFactory,
        SubscriberFactory $subscriberFactory,
        RegionInterfaceFactory $regionDataFactory,
        AddressInterfaceFactory $addressDataFactory,
        CustomerInterfaceFactory $customerDataFactory,
        CustomerUrl $customerUrl,
        Registration $registration,
        Escaper $escaper,
        CustomerExtractor $customerExtractor,
        DataObjectHelper $dataObjectHelper,
        AccountRedirect $accountRedirect,
        JsonHelper $jsonHelper,
        HelperData $helperData
    ) {
        $this->session             = $customerSession;
        $this->scopeConfig         = $scopeConfig;
        $this->storeManager        = $storeManager;
        $this->accountManagement   = $accountManagement;
        $this->addressHelper       = $addressHelper;
        $this->formFactory         = $formFactory;
        $this->subscriberFactory   = $subscriberFactory;
        $this->regionDataFactory   = $regionDataFactory;
        $this->addressDataFactory  = $addressDataFactory;
        $this->customerDataFactory = $customerDataFactory;
        $this->customerUrl         = $customerUrl;
        $this->registration        = $registration;
        $this->escaper             = $escaper;
        $this->customerExtractor   = $customerExtractor;
        $this->urlFactory          = $urlFactory;
        $this->dataObjectHelper    = $dataObjectHelper;
        $this->accountRedirect     = $accountRedirect;
        $this->jsonHelper          = $jsonHelper;
        $this->helperData          = $helperData;
        parent::__construct($context);
    }

    protected function extractAddress()
    {
        if (!$this->getRequest()->getPost('create_address')) {
            return null;
        }

        $addressForm       = $this->formFactory->create('customer_address', 'customer_register_address');
        $allowedAttributes = $addressForm->getAllowedAttributes();

        $addressData = [];

        $regionDataObject = $this->regionDataFactory->create();
        foreach ($allowedAttributes as $attribute) {
            $attributeCode = $attribute->getAttributeCode();
            $value         = $this->getRequest()->getParam($attributeCode);
            if ($value === null) {
                continue;
            }
            switch ($attributeCode) {
                case 'region_id':
                    $regionDataObject->setRegionId($value);
                    break;
                case 'region':
                    $regionDataObject->setRegion($value);
                    break;
                default:
                    $addressData[$attributeCode] = $value;
            }
        }
        $addressDataObject = $this->addressDataFactory->create();
        $this->dataObjectHelper->populateWithArray(
            $addressDataObject,
            $addressData,
            '\Magento\Customer\Api\Data\AddressInterface'
        );
        $addressDataObject->setRegion($regionDataObject);

        $addressDataObject->setIsDefaultBilling(
            $this->getRequest()->getParam('default_billing', false)
        )->setIsDefaultShipping(
            $this->getRequest()->getParam('default_shipping', false)
        );

        return $addressDataObject;
    }

    public function execute()
    {
        $result = [];
        
        $this->session->regenerateId();
        try {

            $address   = $this->extractAddress();
            $addresses = $address === null ? [] : [$address];

            $customer = $this->customerExtractor->extract('customer_account_create', $this->_request);
            $customer->setAddresses($addresses);

            $password     = $this->getRequest()->getParam('password');
            $confirmation = $this->getRequest()->getParam('password_confirmation');
            $recaptcha_response = $this->getRequest()->getParam('g-recaptcha-response', false);
            if (!$this->checkPasswordConfirmation($password, $confirmation)) {
                $result['error']     = true;
                $result['message'][] = __('Please make sure your passwords match.');
            }

            $captchaError = '';
            if ($recaptcha_response) {
                $model = \Magento\Framework\App\ObjectManager::getInstance()->get('Bss\SocialLogin\Model\Recaptcha');
                $captchaError = $model->verify($recaptcha_response);
            }
            if ( !empty($captchaError) ) {
                $result['error'] = $captchaError;
            } 

            if (!isset($result['error']) || !$result['error']) {
                $customer = $this->accountManagement
                    ->createAccount($customer, $password);

                if ($this->getRequest()->getParam('is_subscribed', false)) {
                    $this->subscriberFactory->create()->subscribeCustomerById($customer->getId());
                }

                $this->_eventManager->dispatch(
                    'customer_register_success',
                    ['account_controller' => $this, 'customer' => $customer]
                );

                $confirmationStatus = $this->accountManagement->getConfirmationStatus($customer->getId());
                if ($confirmationStatus === AccountManagementInterface::ACCOUNT_CONFIRMATION_REQUIRED) {
                    $email = $this->customerUrl->getEmailConfirmationUrl($customer->getEmail());
                    // @codingStandardsIgnoreStart
                    $result['success']   = false;
                    $result['message'][] = __(
                        'You must confirm your account. Please check your email for the confirmation link or <a href="%1">click here</a> for a new link.',
                        $email
                    );
                    $result['redirect'] = $this->helperData->getRedirectUrl('register');
                } else {
                    $result['success']   = true;
                    $result['message'][] = __(
                        'Create an account successfully. Please wait...'
                    );
                    $result['redirect'] = $this->helperData->getRedirectUrl('register');
                    $this->session->setCustomerDataAsLoggedIn($customer);
                }
            }


        } catch (StateException $e) {
            $url             = $this->urlFactory->create()->getUrl('customer/account/forgotpassword');
            $result['error'] = true;

            $result['message'][] = __(
                'There is already an account with this email address. If you are sure that it is your email address, <a href="%1">click here</a> to get your password and access your account.',
                $url
            );
        } catch (InputException $e) {
            $result['error']     = true;
            $result['message'][] = $this->escaper->escapeHtml($e->getMessage());
        } catch (\Exception $e) {
            $result['error']     = true;
            $result['message'][] = $this->escaper->escapeHtml($e->getMessage());
        }

        $this->session->setCustomerFormData($this->getRequest()->getPostValue());

        $this->getResponse()->setBody($this->jsonHelper->jsonEncode($result));
        return;
    }

    protected function checkPasswordConfirmation($password, $confirmation)
    {
        return $password == $confirmation;
    }

}
