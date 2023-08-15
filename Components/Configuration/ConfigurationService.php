<?php


namespace AdyenPayment\Components\Configuration;


use Adyen\Core\BusinessLogic\Domain\Configuration\Configuration;
use Adyen\Core\Infrastructure\Logger\Logger;
use AdyenPayment\Components\ShopwareVersionCheck;
use AdyenPayment\Utilities\Plugin;
use AdyenPayment\Utilities\Url;

class ConfigurationService extends Configuration
{
    private const INTEGRATION_NAME = 'Shopware';
    const MIN_LOG_LEVEL = Logger::WARNING;

    /**
     * @param $guid
     *
     * @return string
     */
    public function getAsyncProcessUrl($guid): string
    {
        $params = ['guid' => $guid];
        if ($this->isAutoTestMode()) {
            $params['auto-test'] = 1;
        }

        return Url::getFrontUrl('AdyenAsyncProcess', 'run', $params);
    }

    /**
     * @return string
     */
    public function getIntegrationVersion(): string
    {
        /** @var ShopwareVersionCheck $versionCheck */
        $versionCheck = Shopware()->Container()->get('adyen_payment.components.shopware_version_check');

        return $versionCheck->getShopwareVersion();
    }

    /**
     * @return string
     */
    public function getIntegrationName(): string
    {
        return self::INTEGRATION_NAME;
    }

    /**
     * @return string
     */
    public function getPluginName(): string
    {
        return 'AdyenPayment';
    }

    /**
     * @return string
     */
    public function getPluginVersion(): string
    {
        return Plugin::getVersion();
    }
}
