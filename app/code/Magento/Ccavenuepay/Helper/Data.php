<?php

namespace Magento\Ccavenuepay\Helper;

use Magento\Payment\Model\Method\Substitution;
use Magento\Quote\Model\Quote;
use Magento\Store\Model\Store;
use Magento\Payment\Block\Form;
use Magento\Payment\Model\InfoInterface;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\LayoutInterface;
use Magento\Framework\View\LayoutFactory;
use Magento\Payment\Model\Method\AbstractMethod;
use Magento\Payment\Model\MethodInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Ccavenuepay\Model\Cbdom_main;

/**
 * Ccavenuepay Data helper
 */
class Data extends \Magento\Framework\App\Helper\AbstractHelper {

    const HTML_TRANSACTION_ID = '<a target="_blank" href="https://www.%1$s.paypal.com/cgi-bin/webscr?cmd=_view-a-trans&id=%2$s">%2$s</a>';
    const METHOD_CODE = 'ccavenuepay';

    /**
     * Cache for shouldAskToCreateBillingAgreement()
     *
     * @var bool
     */
    protected static $_shouldAskToCreateBillingAgreement = null;

    /**
     * @var \Magento\ccavenuepay\Helper\Data
     */
    protected $_paymentData;

    /**
     * @var \Magento\Ccavenuepay\Model\Billing\AgreementFactory
     */
    protected $_agreementFactory;

    /**
     * @var array
     */
    private $methodCodes;
    protected $Cbdom;

    /**
     * @var \Magento\Ccavenuepay\Model\ConfigFactory
     */
    private $configFactory;
    protected $_pgmod_ver = "2.2";    //==> Module Version
    protected $_pgcat = "CCAvenue";   //==>Category
    protected $_pgcat_ver = "MCPG-2.0";   //==>Category Version
    protected $_pgcms = "Magento";   //==>CMS
    protected $_pgcms_ver = "2.0.2";    //==>CMS Version
    protected $_pg_lic_key = 'FREE';    //Payment module license key
    protected $_token = "magento";
    protected $_ccavenuepay_pdf_manual_link = "";
    protected $_ccavenuepay_video_link = "";
    protected $_ccavenuepay_alert_message = "";
    protected $_Cbdom;
    protected $logger;

    /**
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Magento\Payment\Helper\Data $paymentData
     * @param \Magento\Ccavenuepay\Model\Billing\AgreementFactory $agreementFactory
     * @param \Magento\Ccavenuepay\Model\ConfigFactory $configFactory
     * @param array $methodCodes
     */
    public function __construct(
    \Magento\Framework\App\Helper\Context $context, LayoutFactory $layoutFactory, \Magento\Payment\Model\Method\Factory $paymentMethodFactory, \Magento\Store\Model\App\Emulation $appEmulation, \Magento\Payment\Model\Config $paymentConfig, \Magento\Framework\App\Config\Initial $initialConfig, \Magento\Ccavenuepay\Model\Cbdom_main $Cbdom_main
    ) {
        $this->_Cbdom = $Cbdom_main;
        $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/test.log');
        $this->logger = new \Zend\Log\Logger();
        $this->logger->addWriter($writer);
        $this->logger->info("Helper Data extends \Magento\Framework\App\Helper\AbstractHelper");
        parent::__construct($context);
        $this->_layout = $layoutFactory->create();
        $this->_methodFactory = $paymentMethodFactory;
        $this->_appEmulation = $appEmulation;
        $this->_paymentConfig = $paymentConfig;
        $this->_initialConfig = $initialConfig;
        $this->logger->info("Helper Data extends \Magento\Framework\App\Helper\AbstractHelper2");
    }

    /**
     * Check whether customer should be asked confirmation whether to sign a billing agreement
     *
     * @param \Magento\Ccavenuepay\Model\Config $config
     * @param int $customerId
     * @return bool
     */

    /**
     * @param string $code
     * @return string
     */
    protected function getMethodModelConfigName($code) {
        $this->logger->info("getMethodModelConfigName");
        return sprintf('%s/%s/model', self::METHOD_CODE, $code);
    }

    /**
     * Retrieve method model object
     *
     * @param string $code
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     * @return MethodInterface
     */
    public function getMethodInstance($code) {
        $this->logger->info("getMethodInstance");
        $class = $this->scopeConfig->getValue(
                $this->getMethodModelConfigName($code), \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );

        if (!$class) {
            throw new \UnexpectedValueException('Payment model name is not provided in config!');
        }

        return $this->_methodFactory->create($class);
    }

    /**
     * Get HTML representation of transaction id
     *
     * @param string $methodCode
     * @param string $txnId
     * @return string
     */
    public function getHtmlTransactionId($methodCode, $txnId) {
        if (in_array($methodCode, $this->methodCodes)) {
            /** @var \Magento\Ccavenuepay\Model\Config $config */
            $config = $this->configFactory->create()->setMethod($methodCode);
            $sandboxFlag = ($config->getValue('sandboxFlag') ? 'sandbox' : '');
            return sprintf(self::HTML_TRANSACTION_ID, $sandboxFlag, $txnId);
        }
        return $txnId;
    }

    public function getCcavneueReturnUrl(array $params) {
        $this->logger->info("getCcavneueReturnUrl");
        $this->logger->info('getCcavneueReturnUrl');
        return $this->_getUrl('ccavenuepay/ccavenuepay/returnurl', $params);
    }


    public function getCcavenuepayParams($key = '') {
        $CcavenuepayParams = [];
        $CcavenuepayParams = array('module_version' => $this->_pgmod_ver, //==> Module Version
            'category' => $this->_pgcat, //==>Category
            'category_version' => $this->_pgcat_ver, //==>Category Version
            'cms' => $this->_pgcms, //==>CMS
            'cms_version' => $this->_pgcms_ver, //==>CMS Version
            'license_key' => $this->_pg_lic_key, //Payment module license key
            'token' => $this->_token,
            'pdf_manual_link' => $this->_ccavenuepay_pdf_manual_link,
            'VideoLink' => $this->_ccavenuepay_video_link,
            'alert_message' => $this->_ccavenuepay_alert_message);
        $this->logger->info("CcavenuepayParams");
        $this->logger->info($CcavenuepayParams);
        if ($key != '') {
            if (isset($CcavenuepayParams[$key])) {
                return $CcavenuepayParams[$key];
            }
            return '';
        }
        return $CcavenuepayParams;
    }
}
