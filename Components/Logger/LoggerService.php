<?php


namespace AdyenPayment\Components\Logger;


use Adyen\Core\Infrastructure\Configuration\Configuration;
use Adyen\Core\Infrastructure\Logger\Interfaces\ShopLoggerAdapter;
use Adyen\Core\Infrastructure\Logger\LogData;
use Adyen\Core\Infrastructure\Logger\Logger;
use Adyen\Core\Infrastructure\ServiceRegister;
use Shopware\Components\Logger as ShopwareLogger;

class LoggerService implements ShopLoggerAdapter
{
    /**
     * Log level names for corresponding log level codes.
     *
     * @var array
     */
    protected static $logLevelName = array(
        Logger::ERROR => 'ERROR',
        Logger::WARNING => 'WARNING',
        Logger::INFO => 'INFO',
        Logger::DEBUG => 'DEBUG',
    );

    /**
     * @var ShopwareLogger
     */
    protected $logger;

    public function __construct(ShopwareLogger $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Log message in system
     *
     * @param LogData $data
     */
    public function logMessage(LogData $data)
    {
        /** @var Configuration $configService */
        $configService = ServiceRegister::getService(Configuration::CLASS_NAME);
        $minLogLevel = $configService->getMinLogLevel();
        $logLevel = $data->getLogLevel();

        if (($logLevel > $minLogLevel) && !$configService->isDebugModeEnabled()) {
            return;
        }

        $message = 'ADYEN LOG:' . ' | '
            . 'Date: ' . date('d/m/Y') . ' | '
            . 'Time: ' . date('H:i:s') . ' | '
            . 'Log level: ' . self::$logLevelName[$logLevel] . ' | '
            . 'Message: ' . $data->getMessage();
        $context = $data->getContext();
        if (!empty($context)) {
            $contextData = array();
            foreach ($context as $item) {
                $contextData[$item->getName()] = print_r($item->getValue(), true);
            }

            $message .= ' | ' . 'Context data: [' . json_encode($contextData) . ']';
        }

        $message .= "\n";

        switch ($logLevel) {
            case Logger::ERROR:
                $this->logger->error($message);
                break;
            case Logger::WARNING:
                $this->logger->warning($message);
                break;
            case Logger::INFO:
                $this->logger->info($message);
                break;
            case Logger::DEBUG:
                $this->logger->debug($message);
        }
    }
}
