<?php

namespace AdyenPayment\Components\Adyen;

use Adyen\AdyenException;
use Adyen\Service\CheckoutUtility;
use Shopware\Components\Model\ModelManager;
use Shopware\Models\Shop\Shop;

/**
 * Class OriginKeysService
 * @package AdyenPayment\Components\Adyen
 */
// se-remove die(): remove OriginKeys, replace by ClientKey
class OriginKeysService
{
    /**
     * @var ApiFactory
     */
    private $apiFactory;
    /**
     * @var ModelManager
     */
    private $models;

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
        $this->apiFactory = $apiFactory;
        $this->models = $models;
    }

    /**
     * @param array $originDomains
     * @return mixed
     * @throws AdyenException
     */
    public function generate(array $originDomains, Shop $shop)
    {
        $checkoutUtility = new CheckoutUtility(
            $this->apiFactory->provide($shop)
        );

        return $checkoutUtility->originKeys(['originDomains' => $originDomains])['originKeys'];
    }
}
