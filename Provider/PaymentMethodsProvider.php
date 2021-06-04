<?php

declare(strict_types=1);

namespace AdyenPayment\Provider;

use Adyen\AdyenException;
use Adyen\Service\Checkout;
use AdyenPayment\Components\Adyen\ApiClientMap;
use AdyenPayment\Components\Adyen\ApiFactory;
use AdyenPayment\Components\Configuration;
use Shopware\Models\Shop\Shop;

class PaymentMethodsProvider
{
    /**
     * @var ApiClientMap
     */
    private $apiClientMap;
    /**
     * @var Configuration
     */
    private $configuration;
    /**
     * @var ApiFactory
     */
    private $adyenApiFactory;

    public function __construct(
        ApiClientMap $apiClientMap,
        Configuration $configuration,
        ApiFactory $adyenApiFactory
    )
    {
        $this->apiClientMap = $apiClientMap;
        $this->configuration = $configuration;
        $this->adyenApiFactory = $adyenApiFactory;
    }

    public function getPaymentMethods(Shop $shop): array {
        $adyenClient = $this->adyenApiFactory->provide($shop);
        $checkout = new Checkout($adyenClient);

        try {
            $paymentMethods = $checkout->paymentMethods([
                'merchantAccount' => $this->configuration->getMerchantAccount($shop),
            ]);
            print_r($paymentMethods);
            die();
        } catch (AdyenException $e) {
            return [];
        }

        return $paymentMethods;
    }

    /**
     * @throws AdyenException
     */
    public function getCheckout(): Checkout
    {
        return new Checkout(
            $this->apiClientMap->lookup(
                Shopware()->Shop()
            )
        );
    }
}