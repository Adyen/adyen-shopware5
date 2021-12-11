<?php

declare(strict_types=1);

namespace AdyenPayment\Components\Adyen\PaymentMethod;

use AdyenPayment\AdyenPayment;
use AdyenPayment\Collection\Payment\PaymentMeanCollection;
use AdyenPayment\Components\Adyen\Builder\PaymentMethodOptionsBuilderInterface;
use AdyenPayment\Components\Adyen\PaymentMethodService;
use AdyenPayment\Enricher\Payment\PaymentMethodEnricherInterface;
use AdyenPayment\Models\Enum\PaymentMethod\SourceType;
use AdyenPayment\Models\Payment\PaymentMean;
use Shopware\Bundle\StoreFrontBundle\Struct\Attribute;

final class EnrichedPaymentMeanProvider implements EnrichedPaymentMeanProviderInterface
{
    private PaymentMethodService $paymentMethodService;
    private PaymentMethodOptionsBuilderInterface $paymentMethodOptionsBuilder;
    private PaymentMethodEnricherInterface $paymentMethodEnricher;

    public function __construct(
        PaymentMethodService $paymentMethodService,
        PaymentMethodOptionsBuilderInterface $paymentMethodOptionsBuilder,
        PaymentMethodEnricherInterface $paymentMethodEnricher
    ) {
        $this->paymentMethodService = $paymentMethodService;
        $this->paymentMethodOptionsBuilder = $paymentMethodOptionsBuilder;
        $this->paymentMethodEnricher = $paymentMethodEnricher;
    }

    /**
     * @throws \Adyen\AdyenException
     */
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

        return new PaymentMeanCollection(...$paymentMeans->map(
            static function(PaymentMean $shopwareMethod) use ($adyenPaymentMethods, $enricher): ?PaymentMean {
                if (!$shopwareMethod->getSource()->equals(SourceType::adyen())) {
                    return $shopwareMethod;
                }

                /** @var Attribute $attribute */
                $attribute = $shopwareMethod->getValue('attribute');
                if (!$attribute) {
                    return $shopwareMethod;
                }

                $identifierOrStoredId = '' !== (string) $attribute->get(AdyenPayment::ADYEN_STORED_METHOD_ID)
                    ? $attribute->get(AdyenPayment::ADYEN_STORED_METHOD_ID)
                    : $attribute->get(AdyenPayment::ADYEN_CODE);

                $paymentMethod = $adyenPaymentMethods->fetchByIdentifierOrStoredId($identifierOrStoredId);

                if (null === $paymentMethod) {
                    return null;
                }

                return PaymentMean::createFromShopwareArray(($enricher)($shopwareMethod->getRaw(), $paymentMethod));
            }
        ));
    }
}
