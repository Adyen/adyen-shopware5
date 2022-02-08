<?php

declare(strict_types=1);

namespace AdyenPayment\Components\Adyen\PaymentMethod;

use AdyenPayment\Collection\Payment\PaymentMeanCollection;
use AdyenPayment\Collection\Payment\PaymentMethodCollection;
use AdyenPayment\Components\Adyen\Builder\PaymentMethodOptionsBuilderInterface;
use AdyenPayment\Components\Adyen\PaymentMethodServiceInterface;
use AdyenPayment\Enricher\Payment\PaymentMethodEnricherInterface;
use AdyenPayment\Exceptions\UmbrellaPaymentMeanNotFoundException;
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

        $umbrellaPaymentMean = $paymentMeans->fetchStoredMethodUmbrellaPaymentMean();
        if (null === $umbrellaPaymentMean) {
            throw UmbrellaPaymentMeanNotFoundException::missingUmbrellaPaymentMean();
        }

        return new PaymentMeanCollection(
            ...$this->provideEnrichedPaymentMeans($paymentMeans, $adyenPaymentMethods),
            ...$this->provideEnrichedStoredPaymentMeans($adyenPaymentMethods, $umbrellaPaymentMean)
        );
    }

    private function provideEnrichedPaymentMeans(
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

                return PaymentMean::createFromShopwareArray(($enricher)($paymentMean->getRaw(), $paymentMethod));
            });
    }

    private function provideEnrichedStoredPaymentMeans(
        PaymentMethodCollection $adyenPaymentMethods,
        PaymentMean $umbrellaPaymentMean
    ): array {
        $enricher = $this->paymentMethodEnricher;
        $storedAdyenMethods = $adyenPaymentMethods->filterByPaymentType(PaymentGroup::stored());

        return $storedAdyenMethods->map(
            static function(PaymentMethod $paymentMethod) use ($umbrellaPaymentMean, $enricher): PaymentMean {
                return PaymentMean::createFromShopwareArray(
                    ($enricher)($umbrellaPaymentMean->getRaw(), $paymentMethod)
                );
            }
        );
    }
}
