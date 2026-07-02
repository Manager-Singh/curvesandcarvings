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
namespace Bss\SocialLogin\Model\Config\Source;

class RedirectUrl implements \Magento\Framework\Option\ArrayInterface
{

    protected $_options = null;

    public function toOptionArray()
    {
        return $this->_getOptions();
    }

    public function toArray()
    {
        $options = [];
        foreach ($this->_getOptions() as $option) {
            $options[ $option['value'] ] = $option['label'];
        }

        return $options;
    }

    protected function _getOptions()
    {
        if(null === $this->_options) {
            $options = [
                ['value' => '',     'label' => __('Stay on the current page')],
                ['value' => 'custom',      'label' => __('Redirect to Custom URL')],
                ['value' => 'url_dashboard',   'label' => __('Customer -> Account Dashboard')],
                ['value' => 'url_shoppingcart',   'label' => __('Shopping cart page')],
                ['value' => 'url_checkoutpage',   'label' => __('Checkout page')],
            ];

            $items = \Magento\Framework\App\ObjectManager::getInstance()->get('Magento\Cms\Model\Page')->getCollection()->getItems();

            foreach ($items as $item) {
                if($item->getId() == 1) continue;
                $cmspage_url = \Magento\Framework\App\ObjectManager::getInstance()->get('Magento\Cms\Helper\Page')->getPageUrl($item->getId());
                $pos = strpos($cmspage_url, '?');
                $cmspage_url = ($pos>0) ? substr($cmspage_url, 0, $pos) : $cmspage_url;
                $options[] = ['value' => $cmspage_url, 'label' => $item->getTitle()];
            }

            $this->_options = $options;
        }

        return $this->_options;
    }

}