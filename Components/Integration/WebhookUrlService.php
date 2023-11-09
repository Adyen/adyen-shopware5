<?php

namespace AdyenPayment\Components\Integration;

use Adyen\Core\BusinessLogic\Domain\Integration\Webhook\WebhookUrlService as BaseWebhookUrlService;
use Adyen\Core\BusinessLogic\Domain\Multistore\StoreContext;
use Adyen\Core\Infrastructure\Configuration\ConfigurationManager;
use Adyen\Core\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException;
use Adyen\Core\Infrastructure\ServiceRegister;
use AdyenPayment\Utilities\Url;

/**
 * Class WebhookUrlService
 *
 * @package AdyenPayment\BusinessService
 */
class WebhookUrlService implements BaseWebhookUrlService
{
    /**
     * @var StoreContext
     */
    private $storeContext;

    /**
     * @var array
     */
    private static $callbackMap = [
        'host' => '',
        'replace' => '',
    ];

    /**
     * @param StoreContext $storeContext
     */
    public function __construct(StoreContext $storeContext)
    {
        $this->storeContext = $storeContext;
    }

    /**
     * @return string
     * @throws QueryFilterInvalidParamException
     */
    public function getWebhookUrl(): string
    {
        $url = Url::getFrontUrl('AdyenWebhook', 'index', ['storeId' => $this->storeContext->getStoreId()]);

        // only for development purposes
        if (!empty(static::$callbackMap['host']) && !empty(static::$callbackMap['replace'])) {
            $url = str_replace(static::$callbackMap['host'], static::$callbackMap['replace'], $url);
        }

        $testHostname = $this->getConfigurationManager()->getConfigValue('testHostname');
        if($testHostname){
            $url = str_replace('localhost', $testHostname, $url);
        }

        return $url;
    }

    /**
     * @return ConfigurationManager
     *
     */
    private function getConfigurationManager(): ConfigurationManager
    {
        return ServiceRegister::getService(ConfigurationManager::CLASS_NAME);
    }
}
