<?php
declare(strict_types=1);

namespace Curvesandcarvings\Theme\Plugin\Checkout\Cart;

use Magento\Checkout\Controller\Cart\Add;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\ResultInterface;

/**
 * Redirect to checkout after a successful Buy Now add-to-cart.
 *
 * Magento\Checkout\Controller\Cart\Add can return ResultInterface OR an HTTP response
 * interceptor when the request is handled as a redirect/forward fallback.
 */
class AddBuyNowRedirectPlugin
{
    /**
     * @param Add $subject
     * @param mixed $result
     * @return mixed
     */
    public function afterExecute(Add $subject, $result)
    {
        if (!$subject->getRequest()->getParam('cc_buy_now')) {
            return $result;
        }

        if ($result instanceof Redirect) {
            $result->setPath('checkout');
        }

        return $result;
    }
}
