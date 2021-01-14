<?php

namespace AdyenPayment\Components;

use Adyen\AdyenException;
use AdyenPayment\AdyenPayment;
use Shopware\Components\CacheManager;
use Shopware\Components\Model\ModelManager;
use Shopware\Components\Plugin\ConfigWriter;
use Shopware\Models\Plugin\Plugin;
use Shopware\Models\Shop\Shop;

/**
 * Class OriginKeysService
 * @package AdyenPayment\Components
 */
class OriginKeysService
{
    /**
     * @var Adyen\OriginKeysService
     */
    private $originKeysService;

    /**
     * @var ModelManager
     */
    private $models;

    /**
     * @var ConfigWriter
     */
    private $configWriter;
    /**
     * @var ShopwareVersionCheck
     */
    private $shopwareVersionCheck;

    /**
     * OriginKeysService constructor.
     * @param Adyen\OriginKeysService $originKeysService
     * @param ModelManager $models
     * @param ConfigWriter $configWriter
     */
    public function __construct(
        Adyen\OriginKeysService $originKeysService,
        ModelManager $models,
        ConfigWriter $configWriter,
        ShopwareVersionCheck $shopwareVersionCheck
    ) {
        $this->originKeysService = $originKeysService;
        $this->models = $models;
        $this->configWriter = $configWriter;
        $this->shopwareVersionCheck = $shopwareVersionCheck;
    }

    /**
     * @throws AdyenException
     */
    public function generateAndSave()
    {
        $plugin = $this->models->getRepository(Plugin::class)->findOneBy(['name' => AdyenPayment::NAME]);
        $shops = $this->models->getRepository(Shop::class)->findAll();

        foreach ($shops as $shop) {
            $shopOriginKey = $this->provideOriginKey($shop);
            if (!$shopOriginKey) {
                continue;
            }

            $this->configWriter->saveConfigElement(
                $plugin,
                'origin_key',
                $shopOriginKey,
                $shop
            );
        }

        if ($this->shopwareVersionCheck->isHigherThanShopwareVersion('v5.5.6')) {
            Shopware()->Container()->get('shopware.cache_manager')->clearByTags([CacheManager::CACHE_TAG_CONFIG]);
        }
    }

    /**
     * @throws AdyenException
     */
    private function provideOriginKey(Shop $shop): string
    {
        $originKeys = (array) $this->originKeysService->generate(
            (array) $this->getDomain($shop),
            $shop
        );

        if (!$originKeys) {
            return '';
        }

        return (string) array_shift($originKeys);
    }

    /**
     * @param $shop
     * @return string
     */
    private function getDomain($shop)
    {
        $hostName = $shop->getHost();
        $isSecure = $shop->getSecure();
        $mainShop = $shop->getMain();
        if ($mainShop) {
            $hostName = $mainShop->getHost();
            $isSecure = $mainShop->getSecure();
        }

        return ($isSecure ? 'https://' : 'http://') . $hostName;
    }
}
