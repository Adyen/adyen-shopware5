<?php

namespace MeteorAdyen\Components;

use Adyen\AdyenException;
use MeteorAdyen\MeteorAdyen;
use Shopware\Components\Plugin\ConfigWriter;
use Shopware\Components\Model\ModelManager;
use Shopware\Models\Plugin\Plugin;
use Shopware\Models\Shop\Shop;

/**
 * Class OriginKeysService
 * @package MeteorAdyen\Components
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
     * OriginKeysService constructor.
     * @param Adyen\OriginKeysService $originKeysService
     * @param ModelManager $models
     * @param ConfigWriter $configWriter
     */
    public function __construct(
        Adyen\OriginKeysService $originKeysService,
        ModelManager $models,
        ConfigWriter $configWriter
    ) {
        $this->originKeysService = $originKeysService;
        $this->models = $models;
        $this->configWriter = $configWriter;
    }

    /**
     * @param Shop[]|null $shops
     * @return array
     * @throws AdyenException
     */
    public function generate(array $shops = null)
    {
        if (!$shops) {
            $shops = $this->models->getRepository('Shopware\Models\Shop\Shop')->findAll();
        }

        $domains = [];
        foreach ($shops as $shop) {
            $domains[$shop->getId()] = ($shop->getSecure() ? 'https://' : 'http://') .  $shop->getHost();
        }

        $keys = $this->originKeysService->generate(array_values($domains));
        $shopKeys = [];
        foreach ($domains as $shopId => $domain) {
            if (!isset($keys[$domain])) {
                continue;
            }

            $shopKeys[$shopId] = $keys[$domain];
        }

        return $shopKeys;
    }

    /**
     * @throws AdyenException
     */
    public function generateAndSave()
    {
        $plugin = $this->models->getRepository(Plugin::class)->findOneBy(['name' => MeteorAdyen::NAME]);
        $shops = $this->models->getRepository('Shopware\Models\Shop\Shop')->findAll();
        $keys = $this->generate($shops);

        foreach ($shops as $shop) {
            if (!isset($keys[$shop->getId()])) {
                continue;
            }
            $this->configWriter->saveConfigElement(
                $plugin,
                'origin_key',
                $keys[$shop->getId()],
                $shop
            );
        }
    }
}
