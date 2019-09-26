<?php

declare(strict_types=1);

namespace MeteorAdyen\Components;

use Adyen\Environment;
use MeteorAdyen\MeteorAdyen;
use Shopware\Components\Plugin\CachedConfigReader;
use Shopware\Models\Shop\Shop;

/**
 * Class Configuration
 * @package MeteorAdyen\Components
 */
class Configuration
{
    const ENV_TEST = 'TEST';
    const ENV_LIVE = 'LIVE';

    /**
     * @var CachedConfigReader
     */
    private $cachedConfigReader;

    /**
     * Configuration constructor.
     * @param CachedConfigReader $cachedConfigReader
     */
    public function __construct(
        CachedConfigReader $cachedConfigReader
    )
    {
        $this->cachedConfigReader = $cachedConfigReader;
    }

    /**
     * @param bool $shop
     * @return string
     */
    public function getEnvironment($shop = false): string
    {
        if ($this->getConfig('environment', $shop) === self::ENV_TEST) {
            return Environment::TEST;
        }

        return Environment::LIVE;
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
            $shop = Shopware()->Shop();
        }

        $config = $this->cachedConfigReader->getByPluginName(MeteorAdyen::NAME, $shop);

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
    public function getJsComponentsOriginKey($shop = false): string
    {
        return (string)$this->getConfig('js_components_originkey', $shop);
    }

    /**
     * @param bool|Shop $shop
     * @return string
     */
    public function getJsComponents3DS2ChallengeImageSize($shop = false): string
    {
        return (string)$this->getConfig('js_components_3DS2_challenge_image_size', $shop);
    }

    /**
     * @param bool|Shop $shop
     * @return string
     */
    public function getApiKey($shop = false): string
    {
        return (string)$this->getConfig('api_key', $shop);
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
    public function getNotificationHmac($shop = false): string
    {
        return (string)$this->getConfig('notification_hmac', $shop);
    }

    /**
     * @param bool $shop
     * @return string
     */
    public function getNotificationAuthUsername($shop = false): string
    {
        return (string)$this->getConfig('notification_auth_username', $shop);
    }

    /**
     * @param bool $shop
     * @return int
     */
    public function getDebugLogging($shop = false): bool
    {
        return (bool)$this->getConfig('debug_logging', $shop);
    }
}
