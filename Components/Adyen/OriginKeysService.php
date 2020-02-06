<?php

namespace MeteorAdyen\Components\Adyen;

use Adyen\AdyenException;
use Adyen\Service\CheckoutUtility;
use Shopware\Components\Model\ModelManager;
use Shopware\Models\Shop\Shop;

/**
 * Class OriginKeysService
 * @package MeteorAdyen\Components\Adyen
 */
class OriginKeysService
{
    /**
     * @var CheckoutUtility
     */
    private $checkoutUtility;

    /**
     * OriginKeysService constructor.
     * @param ApiFactory $apiFactory
     * @param ModelManager $models
     * @throws AdyenException
     */
    public function __construct(
        ApiFactory $apiFactory,
        ModelManager $models
    ) {
        $mainShop = $models->getRepository(Shop::class)->findOneBy(['default' => 1]);
        $this->checkoutUtility = new CheckoutUtility($apiFactory->create($mainShop));
    }

    /**
     * @param array $originDomains
     * @return mixed
     * @throws AdyenException
     */
    public function generate(array $originDomains)
    {
        return $this->checkoutUtility->originKeys(['originDomains' => $originDomains])['originKeys'];
    }
}
