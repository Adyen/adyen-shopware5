<?php

declare(strict_types=1);

namespace AdyenPayment\Components\Adyen\PaymentMethod;

use AdyenPayment\AdyenPayment;
use AdyenPayment\Collection\Payment\PaymentMeanCollection;
use AdyenPayment\Collection\Payment\PaymentMethodCollection;
use AdyenPayment\Components\Adyen\Builder\PaymentMethodOptionsBuilderInterface;
use AdyenPayment\Components\Adyen\PaymentMethodServiceInterface;
use AdyenPayment\Enricher\Payment\PaymentMethodEnricherInterface;
use AdyenPayment\Models\Enum\PaymentMethod\SourceType;
use AdyenPayment\Models\Payment\PaymentGroup;
use AdyenPayment\Models\Payment\PaymentMean;
use AdyenPayment\Models\Payment\PaymentMethod;

final class EnrichedPaymentMeanProvider implements EnrichedPaymentMeanProviderInterface
{
    private PaymentMethodServiceInterface $paymentMethodService;
    private PaymentMethodOptionsBuilderInterface $paymentMethodOptionsBuilder;
    private PaymentMethodEnricherInterface $paymentMethodEnricher;

    public function __construct(
        PaymentMethodServiceInterface $paymentMethodService,
        PaymentMethodOptionsBuilderInterface $paymentMethodOptionsBuilder,
        PaymentMethodEnricherInterface $paymentMethodEnricher
    ) {
        $this->paymentMethodService = $paymentMethodService;
        $this->paymentMethodOptionsBuilder = $paymentMethodOptionsBuilder;
        $this->paymentMethodEnricher = $paymentMethodEnricher;
    }

    public function __invoke(PaymentMeanCollection $paymentMeans): PaymentMeanCollection
    {
        $paymentMethodOptions = ($this->paymentMethodOptionsBuilder)();
        if (0.0 === $paymentMethodOptions['value']) {
            return $paymentMeans->filterExcludeAdyen();
        }

        $adyenPaymentMethods = $this->paymentMethodService->getPaymentMethods(
            $paymentMethodOptions['countryCode'],
            $paymentMethodOptions['currency'],
            $paymentMethodOptions['value']
        );

        /** @var ?PaymentMean $umbrellaPaymentMean */
        $umbrellaPaymentMean = $paymentMeans->map(static function(PaymentMean $method) {
            if (AdyenPayment::ADYEN_STORED_PAYMENT_UMBRELLA_CODE === $method->getRaw()['name']) {
                return $method;
            }
        })[0] ?? null;

        return new PaymentMeanCollection(
            ...$this->createAdyenPaymentMeans($paymentMeans, $adyenPaymentMethods),
            ...(null === $umbrellaPaymentMean ? [] : $this->createAdyenStoredPaymentMeans(
                $adyenPaymentMethods,
                $umbrellaPaymentMean
            ))
        );
    }

    private function createAdyenPaymentMeans(
        PaymentMeanCollection $paymentMeans,
        PaymentMethodCollection $adyenPaymentMethods
    ): array {
        $enricher = $this->paymentMethodEnricher;

        return $paymentMeans
            ->filterExcludeHidden()
            ->map(static function(PaymentMean $paymentMean) use ($adyenPaymentMethods, $enricher): ?PaymentMean {
                if (!$paymentMean->getSource()->equals(SourceType::adyen())) {
                    return $paymentMean;
                }

                $paymentMethod = $adyenPaymentMethods->fetchByPaymentMean($paymentMean);
                if (null === $paymentMethod) {
                    return null;
                }

                return PaymentMean::createFromShopwareArray(
                    ($enricher)($paymentMean->getRaw(), $paymentMethod)
                );
            });
    }

    private function createAdyenStoredPaymentMeans(
        PaymentMethodCollection $adyenPaymentMethods,
        PaymentMean $umbrellaPaymentMean
    ): array {
        $enricher = $this->paymentMethodEnricher;
        $storedAdyenMethods = $adyenPaymentMethods->filterByPaymentType(PaymentGroup::stored());

        return $storedAdyenMethods->map(static function(PaymentMethod $paymentMethod) use ($umbrellaPaymentMean, $enricher): PaymentMean {
            $shopwareMethod = $umbrellaPaymentMean->getRaw();
            $shopwareMethod['name'] = $shopwareMethod['description'] = $paymentMethod->getValue('name');
            $shopwareMethod[AdyenPayment::ADYEN_STORED_PAYMENT_UMBRELLA_CODE.'_id'] = $shopwareMethod['id'];
            $shopwareMethod['id'] = $paymentMethod->getStoredPaymentMethodId();

            return PaymentMean::createFromShopwareArray(
                ($enricher)($shopwareMethod, $paymentMethod)
            );
        });
    }
}
