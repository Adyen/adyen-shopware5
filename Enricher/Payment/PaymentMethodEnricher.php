<?php

declare(strict_types=1);

namespace AdyenPayment\Enricher\Payment;

use AdyenPayment\AdyenPayment;
use AdyenPayment\Components\Adyen\PaymentMethod\ImageLogoProviderInterface;
use AdyenPayment\Models\Enum\PaymentMethod\SourceType;
use AdyenPayment\Models\Payment\PaymentMethod;
use Shopware\Bundle\StoreFrontBundle\Struct\Attribute;
use Shopware_Components_Snippet_Manager;

final class PaymentMethodEnricher implements PaymentMethodEnricherInterface
{
    private Shopware_Components_Snippet_Manager $snippets;
    private ImageLogoProviderInterface $imageLogoProvider;

    public function __construct(
        Shopware_Components_Snippet_Manager $snippets,
        ImageLogoProviderInterface $imageLogoProvider
    ) {
        $this->snippets = $snippets;
        $this->imageLogoProvider = $imageLogoProvider;
    }

    public function __invoke(array $shopwareMethod, PaymentMethod $paymentMethod): array
    {
        return array_merge($shopwareMethod, [
            'enriched' => true,
            'additionaldescription' => $this->enrichAdditionalDescription($paymentMethod),
            'image' => $this->imageLogoProvider->provideByType($paymentMethod->adyenType()->type()),
            'isStoredPayment' => $paymentMethod->isStoredPayment(),
            'isAdyenPaymentMethod' => true,
            'adyenType' => $paymentMethod->adyenType()->type(),
            'metadata' => $paymentMethod->rawData(),
        ],
            $this->enrichStoredPaymentMethodData($shopwareMethod, $paymentMethod)
        );
    }

    private function enrichAdditionalDescription(PaymentMethod $adyenMethod): string
    {
        $description = $this->snippets
            ->getNamespace('adyen/method/description')
            ->get($adyenMethod->adyenType()->type()) ?? '';

        if (!$adyenMethod->isStoredPayment()) {
            return $description;
        }

        return sprintf(
            '%s%s: %s',
            ($description ? $description.' ' : ''),
            $this->snippets
                ->getNamespace('adyen/checkout/payment')
                ->get('CardNumberEndingOn', 'Card number ending on', true),
            $adyenMethod->getValue('lastFour', '')
        );
    }

    private function enrichStoredPaymentMethodData(array $shopwareMethod, PaymentMethod $paymentMethod): array
    {
        if (!$paymentMethod->isStoredPayment()) {
            return [];
        }

        $attribute = $shopwareMethod['attribute'] ?? new Attribute();
        $attribute->set(AdyenPayment::ADYEN_STORED_METHOD_ID, $paymentMethod->getStoredPaymentMethodId());

        return [
            'stored_method_umbrella_id' => sprintf(
                '%s_%s',
                $shopwareMethod['id'],
                $paymentMethod->getStoredPaymentMethodId()
            ),
            'stored_method_id' => $paymentMethod->getStoredPaymentMethodId(),
            'description' => $paymentMethod->getValue('name'),
            'source' => SourceType::adyen()->getType(),
            'attribute' => $attribute,
        ];
    }
}
