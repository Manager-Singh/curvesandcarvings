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
namespace Bss\SocialLogin\Controller\Account;

use Magento\Framework\Controller\ResultFactory; 

class LoginPost extends \Magento\Framework\App\Action\Action
{
    protected $urlInterface;

    protected $customerurl;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\UrlInterface $urlInterface,
        \Magento\Customer\Model\Url $customerurl
    ) {
        $this->urlInterface = $urlInterface;
        $this->customerurl = $customerurl;
        parent::__construct($context);
    }
    public function execute()
    {
    	$redirectUrl = $this->getRequest()->getParam('refress-redirect-url');

        if ($redirectUrl) {
            $redirectUrl = base64_decode($redirectUrl);
            $this->getResponse()->setRedirect($redirectUrl);
        } else {
            $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
            $resultRedirect->setUrl($this->_redirect->getRefererUrl());
            return $resultRedirect;
        }
    }
}