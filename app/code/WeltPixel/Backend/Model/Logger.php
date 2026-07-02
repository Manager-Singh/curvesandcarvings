<?php
namespace WeltPixel\Backend\Model;

use DateTimeZone;
use Magento\Framework\App\Config\ScopeConfigInterface;

class Logger extends \Magento\Framework\Logger\Monolog
{
    const XML_PATH_WELTPIXEL_DEVELOPER_LOGGING = 'weltpixel_backend_developer/logging/disable_broken_reference';

    /**
     * @var ScopeConfigInterface|null
     */
    protected $scopeConfig;

    /**
     * @param string $name
     * @param array $handlers
     * @param array $processors
     * @param DateTimeZone|null $timezone
     * @param ScopeConfigInterface|null $scopeConfig
     */
    public function __construct(
        string $name,
        array $handlers = [],
        array $processors = [],
        ?DateTimeZone $timezone = null,
        ?ScopeConfigInterface $scopeConfig = null
    ) {
        $this->scopeConfig = $scopeConfig;
        parent::__construct($name, $handlers, $processors, $timezone);
    }

    /**
     * Adds a log record at the WARNING level.
     *
     * This method allows for compatibility with common interfaces.
     *
     * @param  string  $message The log message
     * @param  array   $context The log context
     * @return Boolean Whether the record has been processed
     */
    public function warning(\Stringable|string $message, array $context = []): void
    {
        $result = $this->_parseLogMessage($message, $context);
        if ($result !== false) {
            parent::warning($message, $context);
        }
    }

    /**
     * Adds a log record at the INFO level.
     *
     * This method allows for compatibility with common interfaces.
     *
     * @param  string  $message The log message
     * @param  array   $context The log context
     * @return Boolean Whether the record has been processed
     */
    public function info(\Stringable|string $message, array $context = []): void
    {
        $result = $this->_parseLogMessage($message, $context);
        if ($result !== false) {
            parent::info($message, $context);
        }
    }

    /**
     * @param $message
     * @param array $context
     * @return Boolean
     */
    protected function _parseLogMessage($message, $context)
    {
        if (!$this->scopeConfig) {
            return true;
        }
        $isLogEnabled = $this->scopeConfig->getValue(self::XML_PATH_WELTPIXEL_DEVELOPER_LOGGING, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        $pos = strpos($message, 'Broken reference');
        if (!$isLogEnabled && ($pos !== false) ) {
            return false;
        }

        return true;
    }
}
