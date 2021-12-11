<?php

declare(strict_types=1);

namespace AdyenPayment\Components\Adyen\PaymentMethod;

use Adyen\AdyenException;
use Adyen\Service\Checkout;
use AdyenPayment\Collection\Payment\PaymentMethodCollection;
use AdyenPayment\Components\Adyen\ApiFactory;
use AdyenPayment\Components\Adyen\PaymentMethodService;
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

    public function __invoke(Shop $shop): PaymentMethodCollection
    {
        try {
            $merchantAccount = $this->configuration->getMerchantAccount($shop);
            $adyenClient = $this->adyenApiFactory->provide($shop);
            $checkout = new Checkout($adyenClient);

            $paymentMethods = PaymentMethodCollection::fromAdyenMethods($checkout->paymentMethods([
                'merchantAccount' => $merchantAccount,
                'shopperLocale' => PaymentMethodService::IMPORT_LOCALE,
            ]));

            return $paymentMethods->withImportLocale($paymentMethods);
        } catch (AdyenException $e) {
            $this->logger->error($e->getMessage(), [
                'merchantAccount' => $merchantAccount ?? 'n/a',
                'Shop' => $shop->getName(),
                'trace' => $e->getTraceAsString(),
            ]);
        }

        return new PaymentMethodCollection();
    }
}
