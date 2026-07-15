<?php
declare(strict_types=1);

namespace Curvesandcarvings\Theme\Block\Review;

use Magento\Customer\Model\Context;
use Magento\Customer\Model\Url;
use Magento\Framework\Registry;

/**
 * Review form: resolve product ID on SEO URLs; guests must log in to write.
 * Posted reviews remain visible to everyone via the reviews list.
 */
class Form extends \Magento\Review\Block\Form
{
    private ?Registry $ccRegistry = null;

    private function getCcRegistry(): Registry
    {
        if ($this->ccRegistry === null) {
            $this->ccRegistry = \Magento\Framework\App\ObjectManager::getInstance()->get(Registry::class);
        }
        return $this->ccRegistry;
    }

    protected function _construct()
    {
        parent::_construct();

        $isLoggedIn = (bool)$this->httpContext->getValue(Context::CONTEXT_AUTH);
        $this->setAllowWriteReviewFlag($isLoggedIn);

        if (!$isLoggedIn) {
            $queryParam = $this->urlEncoder->encode(
                $this->getUrl('*/*/*', ['_current' => true]) . '#review-form'
            );
            $this->setLoginLink(
                $this->getUrl(
                    'customer/account/login/',
                    [Url::REFERER_QUERY_PARAM_NAME => $queryParam]
                )
            );
        }
    }

    protected function getProductId()
    {
        $id = (int)$this->getRequest()->getParam('id', false);
        if ($id) {
            return $id;
        }
        $product = $this->getCcRegistry()->registry('current_product')
            ?: $this->getCcRegistry()->registry('product');
        return $product ? (int)$product->getId() : 0;
    }
}
