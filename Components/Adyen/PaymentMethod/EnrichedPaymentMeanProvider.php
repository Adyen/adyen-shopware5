<?php

declare(strict_types=1);

namespace AdyenPayment\Components\Adyen\PaymentMethod;

use AdyenPayment\Collection\Payment\PaymentMeanCollection;
use AdyenPayment\Components\Adyen\Builder\PaymentMethodOptionsBuilderInterface;
use AdyenPayment\Components\Adyen\PaymentMethodServiceInterface;
use AdyenPayment\Enricher\Payment\PaymentMethodEnricherInterface;
use AdyenPayment\Models\Enum\PaymentMethod\SourceType;
use AdyenPayment\Models\Payment\PaymentMean;

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

        $enricher = $this->paymentMethodEnricher;

        return new PaymentMeanCollection(...$paymentMeans
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
            })
        );
    }
}
