<?php

use Adyen\Core\BusinessLogic\AdminAPI\AdminAPI;
use Adyen\Core\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException;
use Adyen\Core\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException;
use AdyenPayment\Controllers\Common\AjaxResponseSetter;
use AdyenPayment\Repositories\Wrapper\PaymentMeanRepository;

/**
 * Class Shopware_Controllers_Backend_AdyenSystemInfo
 */
class Shopware_Controllers_Backend_AdyenSystemInfo extends Enlight_Controller_Action
{
    use AjaxResponseSetter {
        AjaxResponseSetter::preDispatch as protected ajaxResponseSetterPreDispatch;
    }
    private const SYSTEM_INFO_FILE_NAME = 'adyen-debug-data.zip';
    private const PHP_INFO_FILE_NAME = 'phpinfo.html';
    private const QUEUE_INFO_FILE_NAME = 'queue.json';
    private const CONFIGURED_PAYMENT_METHODS = 'adyen-payment-methods.json';
    private const SYSTEM_INFO = 'system-info.json';
    private const AUTO_TEST = 'auto-test.json';
    private const CONNECTION_SETTINGS = 'connection-settings.json';
    private const WEBHOOK_VALIDATION = 'webhook-validation.json';
    private const CUTOFF = 604800;
    private const ADYEN_SPECIFIC_LOGS = 'adyen-logs.txt';
    private const ADYEN_SPECIFIC_LOG_FILE_NAME = 'adyen_payment';
    private const SHOPWARE_LOG_FILE = 'system-logs.txt';
    private const SHOPWARE_PAYMENT_METHODS= 'shopware-adyen-payment-methods.json';
    private const CORE = 'core';

    /**
     * @var PaymentMeanRepository
     */
    private $repository;

    /**
     * @return void
     * @throws Exception
     */
    public function preDispatch(): void
    {
        $this->ajaxResponseSetterPreDispatch();
        $this->repository = $this->get(PaymentMeanRepository::class);
    }

    /**
     * @return void
     *
     * @throws QueryFilterInvalidParamException
     * @throws RepositoryNotRegisteredException
     * @throws Exception
     */
    public function systemInfoAction(): void
    {
        $file = $this->createZip();

        $response = $this->Response();
        $response->setHeader('content-description', 'File Transfer');
        $response->setHeader('content-type', 'application/octet-stream');
        $response->setHeader('content-disposition', 'attachment; filename=' . self::SYSTEM_INFO_FILE_NAME);
        $response->setHeader('cache-control', 'public', true);
        $response->setHeader('content-length', (string)filesize($file));
        $response->sendHeaders();

        $this->Front()->Plugins()->ViewRenderer()->setNoRender();

        $out = fopen('php://output', 'wb');
        $file = fopen($file, 'rb');

        stream_copy_to_stream($file, $out);
    }

    /**
     * @throws QueryFilterInvalidParamException
     * @throws RepositoryNotRegisteredException
     */
    private function createZip()
    {
        $file = tempnam(sys_get_temp_dir(), 'adyen_system_info');

        $zip = new ZipArchive();
        $zip->open($file, ZipArchive::CREATE);

        $info = AdminAPI::get()->systemInfo()->getSystemInfo()->toArray();
        $autoTestReport = AdminAPI::get()->autoTest()->autoTestReport()->toArray();

        $zip->addFromString(self::PHP_INFO_FILE_NAME, $info['phpInfo']);
        $zip->addFromString(
            self::QUEUE_INFO_FILE_NAME,
            json_encode($info['queueItems'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        );
        $zip->addFromString(
            self::CONFIGURED_PAYMENT_METHODS,
            json_encode($info['paymentMethods'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        );
        $zip->addFromString(
            self::SYSTEM_INFO,
            json_encode($info['systemInfo'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        );
        $zip->addFromString(
            self::CONNECTION_SETTINGS,
            json_encode($info['connectionSettings'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        );
        $zip->addFromString(self::WEBHOOK_VALIDATION, $info['webhookValidation']);
        $zip->addFromString(
            self::AUTO_TEST,
            json_encode($autoTestReport, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        );
        $zip->addFromString(self::ADYEN_SPECIFIC_LOGS, self::getLogs(self::ADYEN_SPECIFIC_LOG_FILE_NAME));
        $zip->addFromString(self::SHOPWARE_LOG_FILE, self::getLogs(self::CORE));
        $zip->addFromString(
            self::SHOPWARE_PAYMENT_METHODS,
            json_encode($this->repository->getAdyenPaymentMeans(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        );

        $zip->close();

        return $file;
    }

    /**
     * Retrieves contents of log files.
     *
     * @param $logFileName
     *
     * @return string
     */
    protected static function getLogs($logFileName): string
    {
        $dir = Shopware()->DocPath('var/log');
        $files = glob($dir . $logFileName . '*.log');
        $filesWithTimeStamp = [];
        $cutoff = time() - self::CUTOFF;

        foreach ($files as $fileName) {
            $time = filectime($fileName);
            if ($time >= $cutoff) {
                $filesWithTimeStamp[] = [
                    'path' => $fileName,
                    'timestamp' => $time,
                ];
            }
        }

        $result = '';

        if (!empty($filesWithTimeStamp)) {
            array_multisort(array_column($filesWithTimeStamp, 'timestamp'), SORT_ASC, $filesWithTimeStamp);
            foreach ($filesWithTimeStamp as $item) {
                if ($contents = file_get_contents($item['path'])) {
                    $result .= $contents . "\n";
                }
            }
        }

        return $result;
    }
}
