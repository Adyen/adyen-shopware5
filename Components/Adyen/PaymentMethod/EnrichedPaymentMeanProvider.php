<?php

declare(strict_types=1);

namespace AdyenPayment\Components\Adyen\PaymentMethod;

use AdyenPayment\Collection\Payment\PaymentMeanCollection;
use AdyenPayment\Collection\Payment\PaymentMethodCollection;
use AdyenPayment\Components\Adyen\Builder\PaymentMethodOptionsBuilderInterface;
use AdyenPayment\Components\Adyen\PaymentMethodService;
use AdyenPayment\Enricher\Payment\PaymentMethodEnricherInterface;

final class EnrichedPaymentMeanProvider implements EnrichedPaymentMeanProviderInterface
{
    private PaymentMethodService $paymentMethodService;
    private PaymentMethodOptionsBuilderInterface $paymentMethodOptionsBuilder;
    private PaymentMethodEnricherInterface $paymentMethodEnricher;

    public function __construct(
        PaymentMethodService $paymentMethodService,
        PaymentMethodOptionsBuilderInterface $paymentMethodOptionsBuilder,
        PaymentMethodEnricherInterface $paymentMethodEnricher,
    ) {
        $this->paymentMethodService = $paymentMethodService;
        $this->paymentMethodOptionsBuilder = $paymentMethodOptionsBuilder;
        $this->paymentMethodEnricher = $paymentMethodEnricher;
    }

    public function __invoke(PaymentMeanCollection $paymentMeans): PaymentMeanCollection
    {
        $paymentMethodOptions = ($this->paymentMethodOptionsBuilder)();
        if (0 === $paymentMethodOptions['value']) {
            return $paymentMeans->filterExcludeAdyen();
        }

        $adyenPaymentMethods = PaymentMethodCollection::fromAdyenMethods(
            $this->paymentMethodService->getPaymentMethods(
                $paymentMethodOptions['countryCode'],
                $paymentMethodOptions['currency'],
                $paymentMethodOptions['value']
            )
        );

        return $paymentMeans->enrichAdyenPaymentMeans($adyenPaymentMethods, $this->paymentMethodEnricher);
    }
}
