<?php

declare(strict_types=1);

namespace AdyenPayment\Components;

use Adyen\Environment;
use AdyenPayment\AdyenPayment;
use Doctrine\DBAL\Connection;
use Shopware\Components\Plugin\Configuration\ReaderInterface;
use Shopware\Models\Shop\Shop;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;

final class Configuration
{
    public const ENV_TEST = 'TEST';
    public const ENV_LIVE = 'LIVE';
    private ReaderInterface $cachedConfigReader;

    /** @var Connection */
    private $connection;

    /**
     * Configuration constructor.
     */
    public function __construct(
        ReaderInterface $cachedConfigReader,
        Connection $connection
    ) {
        $this->cachedConfigReader = $cachedConfigReader;
        $this->connection = $connection;
    }

    /**
     * @param false|Shop $shop
     */
    public function getEnvironment($shop = false): string
    {
        return self::ENV_LIVE === mb_strtoupper($this->getConfig('environment', $shop))
            ? Environment::LIVE
            : Environment::TEST;
    }

    /**
     * @param bool $shop
     */
    public function isTestModus($shop = false): bool
    {
        return Environment::TEST === $this->getEnvironment($shop);
    }

    /**
     * @param false|Shop $shop
     */
    public function getMerchantAccount($shop = false): string
    {
        return (string) $this->getConfig('merchant_account', $shop);
    }

    /**
     * @param false|Shop $shop
     */
    public function getConfig(?string $key = null, $shop = false)
    {
        if (!$shop) {
            try {
                $shop = Shopware()->Shop();
            } catch (ServiceNotFoundException $exception) {
                //The Shop service is not available in the context (i.e. getting the config from the Backend)
                $shop = null;
            }
        }

        $shopId = $shop ? $shop->getId() : null;
        $config = $this->cachedConfigReader->getByPluginName(AdyenPayment::NAME, $shopId);
        if (null === $key) {
            return $config;
        }

        if (array_key_exists($key, $config)) {
            return $config[$key];
        }
    }

    /**
     * @param false|Shop $shop
     */
    public function getApiKey($shop = false): string
    {
        return (string) $this->getConfig(
            'api_key_'.$this->getEnvironment($shop),
            $shop
        );
    }

    /**
     * @param false|Shop $shop
     */
    public function getApiUrlPrefix($shop = false): string
    {
        return (string) $this->getConfig('api_url_prefix', $shop);
    }

    public function getClientKey(Shop $shop): string
    {
        return (string) $this->getConfig('client_key_'.$this->getEnvironment($shop), $shop);
    }

    /**
     * @param bool|Shop $shop
     */
    public function getNotificationHmac($shop = false): string
    {
        return (string) $this->getConfig(
            'notification_hmac_'.$this->getEnvironment($shop),
            $shop
        );
    }

    /**
     * @param bool $shop
     */
    public function getNotificationAuthUsername($shop = false): string
    {
        return (string) $this->getConfig(
            'notification_auth_username_'.$this->getEnvironment($shop),
            $shop
        );
    }

    /**
     * @param bool $shop
     */
    public function getNotificationAuthPassword($shop = false): string
    {
        return (string) $this->getConfig(
            'notification_auth_password_'.$this->getEnvironment($shop),
            $shop
        );
    }

    /**
     * @param bool $shop
     */
    public function getGoogleMerchantId($shop = false): string
    {
        return (string) $this->getConfig('google_merchant_id', $shop);
    }

    /**
     * @param bool $shop
     */
    public function isPaymentmethodsCacheEnabled($shop = false): bool
    {
        return (bool) $this->getConfig('paymentmethods_cache', $shop);
    }

    /**
     * @param bool $shop
     */
    public function getManualReviewRejectAction($shop = false): string
    {
        return (string) $this->getConfig('manual_review_rejected_action', $shop);
    }

    /**
     * @throws \Doctrine\DBAL\DBALException
     */
    public function getCurrentPluginVersion(): int
    {
        $sql = 'SELECT version FROM s_core_plugins WHERE plugin_name = ? ORDER BY version DESC';
        $stmt = $this->connection->prepare($sql);
        $stmt->execute([AdyenPayment::NAME]);

        return (int) $stmt->fetchColumn();
    }
}
