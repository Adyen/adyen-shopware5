<?php

declare(strict_types=1);

namespace AdyenPayment\Components\Adyen\PaymentMethod;

use Adyen\AdyenException;
use Adyen\Service\Checkout;
use AdyenPayment\Components\Adyen\ApiFactory;
use AdyenPayment\Components\Configuration;
use Psr\Log\LoggerInterface;
use Shopware\Models\Shop\Shop;

final class PaymentMethodsProvider implements PaymentMethodsProviderInterface
{
    /**
     * @var Configuration
     */
    private $configuration;
    /**
     * @var ApiFactory
     */
    private $adyenApiFactory;
    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        Configuration $configuration,
        ApiFactory $adyenApiFactory
    ) {
        $this->configuration = $configuration;
        $this->adyenApiFactory = $adyenApiFactory;
    }

    public function __invoke(Shop $shop): array
    {
        try {
            $adyenClient = $this->adyenApiFactory->provide($shop);
            $checkout = new Checkout($adyenClient);

            $paymentMethods = $checkout->paymentMethods([
                'merchantAccount' => $merchantAccount = $this->configuration->getMerchantAccount($shop),
            ]);
        } catch (AdyenException $e) {
            $this->logger->error($e->getMessage(), [
                'merchantAccount' => $merchantAccount,
                'Shop' => $shop->getName(),
                'trace' => $e->getTraceAsString(),
            ]);
        }

        return $paymentMethods;
    }
}
