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
namespace Bss\SocialLogin\Controller\Adminhtml\Grid;

class Index extends \Magento\Backend\App\Action{
  
	protected $resultPageFactory = false;
	
	public function __construct(
		\Magento\Backend\App\Action\Context $context,
		\Magento\Framework\View\Result\PageFactory $resultPageFactory
	) {
		parent::__construct($context);
		$this->resultPageFactory = $resultPageFactory;
	}

	public function execute()
	{
		$resultPage = $this->resultPageFactory->create();

		$resultPage->setActiveMenu('Bss_SocialLogin::log');
		$resultPage->getConfig()->getTitle()->prepend(__('Records Social Login'));

		$resultPage->addBreadcrumb(__('SocialLogin'), __('Records Social Login'));
		$resultPage->addBreadcrumb(__('SocialLogin'), __('Records Social Login'));

		return $resultPage;
	}

	protected function _isAllowed()
	{
		return $this->_authorization->isAllowed('Bss_SocialLogin::log');
	}
}

