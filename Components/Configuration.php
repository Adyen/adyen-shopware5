<?php

declare(strict_types=1);

namespace AdyenPayment\Components;

use Adyen\Environment;
use Doctrine\DBAL\Connection;
use AdyenPayment\AdyenPayment;
use Shopware\Components\Plugin\CachedConfigReader;
use Shopware\Models\Shop\Shop;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;

/**
 * Class Configuration
 * @package AdyenPayment\Components
 */
class Configuration
{
    const ENV_TEST = 'TEST';
    const ENV_LIVE = 'LIVE';
    const PAYMENT_PREFIX = 'adyen_';
    const PAYMENT_LENGHT = 6;

    /**
     * @var CachedConfigReader
     */
    private $cachedConfigReader;

    /** @var Connection */
    private $connection;

    /**
     * Configuration constructor.
     * @param CachedConfigReader $cachedConfigReader
     * @param Connection $connection
     */
    public function __construct(
        CachedConfigReader $cachedConfigReader,
        Connection $connection
    ) {
        $this->cachedConfigReader = $cachedConfigReader;
        $this->connection = $connection;
    }

    /**
     * @param bool $shop
     * @return string
     */
    public function getEnvironment($shop = false, $lowercase = false): string
    {
        $environment = Environment::TEST;
        if ($this->getConfig('environment', $shop) === self::ENV_LIVE) {
            $environment = Environment::LIVE;
        }

        if ($lowercase) {
            return strtolower($environment);
        }

        return $environment;
    }

    /**
     * @param bool $shop
     * @return bool
     */
    public function isTestModus($shop = false): bool
    {
        return $this->getEnvironment($shop) === Environment::TEST;
    }

    /**
     * @param bool|Shop $shop
     * @return string
     */
    public function getMerchantAccount($shop = false): string
    {
        return (string)$this->getConfig('merchant_account', $shop);
    }

    /**
     * @param string $key
     * @param bool|Shop $shop
     * @return mixed
     */
    public function getConfig($key = null, $shop = false)
    {
        if (!$shop) {
            try {
                $shop = Shopware()->Shop();
            } catch (ServiceNotFoundException $exception) {
                //The Shop service is not available in the context (i.e. getting the config from the Backend)
                $shop = null;
            }
        }

        $config = $this->cachedConfigReader->getByPluginName(AdyenPayment::NAME, $shop);

        if ($key === null) {
            return $config;
        }

        if (array_key_exists($key, $config)) {
            return $config[$key];
        }

        return null;
    }

    /**
     * @param bool|Shop $shop
     * @return string
     */
    public function getApiKey($shop = false): string
    {
        return (string)$this->getConfig(
            'api_key_' . $this->getEnvironment($shop, true),
            $shop
        );
    }

    /**
     * @param bool|Shop $shop
     * @return string
     */
    public function getApiUrlPrefix($shop = false): string
    {
        return (string)$this->getConfig('api_url_prefix', $shop);
    }

    /**
     * @param bool|Shop $shop
     * @return string
     */
    public function getOriginKey($shop = false): string
    {
        return (string)$this->getConfig('origin_key', $shop);
    }

    /**
     * @param bool|Shop $shop
     * @return string
     */
    public function getNotificationHmac($shop = false): string
    {
        return (string)$this->getConfig(
            'notification_hmac_' . $this->getEnvironment($shop, true),
            $shop
        );
    }

    /**
     * @param bool $shop
     * @return string
     */
    public function getNotificationAuthUsername($shop = false): string
    {
        return (string)$this->getConfig(
            'notification_auth_username_' . $this->getEnvironment($shop, true),
            $shop
        );
    }

    /**
     * @param bool $shop
     * @return string
     */
    public function getNotificationAuthPassword($shop = false): string
    {
        return (string)$this->getConfig(
            'notification_auth_password_' . $this->getEnvironment($shop, true),
            $shop
        );
    }

    /**
     * @param bool $shop
     * @return string
     */
    public function getGoogleMerchantId($shop = false): string
    {
        return (string)$this->getConfig('google_merchant_id', $shop);
    }

    /**
     * @param bool $shop
     * @return bool
     */
    public function isPaymentmethodsCacheEnabled($shop = false): bool
    {
        return (bool)$this->getConfig('paymentmethods_cache', $shop);
    }

    /**
     * @return string
     */
    public function getPaymentMethodPrefix(): string
    {
        return (string)self::PAYMENT_PREFIX;
    }

    /**
     * @return int
     * @throws \Doctrine\DBAL\DBALException
     */
    public function getCurrentPluginVersion(): int
    {
        $sql = 'SELECT version FROM s_core_plugins WHERE plugin_name = ? ORDER BY version DESC';
        $stmt = $this->connection->prepare($sql);
        $stmt->execute([AdyenPayment::NAME]);

        return (int)$stmt->fetchColumn();
    }
}
