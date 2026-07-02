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

use Magento\Store\Model\ScopeInterface;

class Callbackurl extends \Magento\Config\Block\System\Config\Form\Field
{
    protected $helper;

    public function __construct(
        \Bss\SocialLogin\Helper\Data $helper,
        \Magento\Backend\Block\Template\Context $context,
        array $data = []
    ) {
        $this->helper = $helper;
        parent::__construct($context, $data);
    }

    protected function _getElementHtml(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        $providerName = str_replace(['sociallogin_', '_callbackurl', 'bss_'], '', $element->getHtmlId());
        $url = $this->helper->getCallbackURL($providerName, true);
        
        return '<input id="'. $element->getHtmlId() .'" type="text" name="" value="'. $url .'" class="input-text sociallogin-callbackurl-autofocus" style="background-color: #EEE; color: #999;" readonly="readonly" />';
    }

}