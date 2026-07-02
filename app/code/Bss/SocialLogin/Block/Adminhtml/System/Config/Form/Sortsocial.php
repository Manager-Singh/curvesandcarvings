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
namespace Bss\SocialLogin\Block\Adminhtml\System\Config\Form;

class Sortsocial extends \Magento\Config\Block\System\Config\Form\Field
{
    protected $helper;

    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Bss\SocialLogin\Helper\Data $helper,
        array $data = []
    ) {
        $this->helper = $helper;
        parent::__construct($context, $data);
    }

    public function _construct()
    {
        parent::_construct();
        $this->setTemplate('system/config/sortsocial.phtml');
    }

    public function render(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        $this->element = $element;
        
        return $this->toHtml();
    }

    public function getButtons()
    {
        return $this->helper->getButtons();
    }

    public function getPreparedButtons()
    {
        return $this->helper->getPreparedButtons();
    }

}