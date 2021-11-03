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
    private Configuration $configuration;
    private ApiFactory $adyenApiFactory;
    private LoggerInterface $logger;

    public function __construct(
        Configuration $configuration,
        ApiFactory $adyenApiFactory,
        LoggerInterface $logger
    ) {
        $this->configuration = $configuration;
        $this->adyenApiFactory = $adyenApiFactory;
        $this->logger = $logger;
    }

    public function __invoke(Shop $shop): array
    {
        try {
            $merchantAccount = $this->configuration->getMerchantAccount($shop);
            $adyenClient = $this->adyenApiFactory->provide($shop);
            $checkout = new Checkout($adyenClient);

            return $checkout->paymentMethods([
                'merchantAccount' => $merchantAccount,
            ]);
        } catch (AdyenException $e) {
            $this->logger->error($e->getMessage(), [
                'merchantAccount' => $merchantAccount ?? 'n/a',
                'Shop' => $shop->getName(),
                'trace' => $e->getTraceAsString(),
            ]);
        }

        return [];
    }
}
