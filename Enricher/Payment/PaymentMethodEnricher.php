<?php

declare(strict_types=1);

namespace AdyenPayment\Enricher\Payment;

use AdyenPayment\Components\Adyen\PaymentMethod\ImageLogoProviderInterface;
use AdyenPayment\Models\Payment\PaymentMethod;
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
            'additionaldescription' => $this->enrichDescription($paymentMethod),
            'image' => $this->imageLogoProvider->provideByType($paymentMethod->getType()),
            'isStoredPayment' => $paymentMethod->isStoredPayment(),
            'isAdyenPaymentMethod' => true,
            'adyenType' => $paymentMethod->getType(),
            'metadata' => $paymentMethod->getRawData(),
        ]);
    }

    private function enrichDescription(PaymentMethod $adyenMethod): string
    {
        $description = $this->snippets
            ->getNamespace('adyen/method/description')
            ->get($adyenMethod->getType()) ?? '';

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
}
