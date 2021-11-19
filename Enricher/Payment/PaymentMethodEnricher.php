<?php

declare(strict_types=1);

namespace AdyenPayment\Enricher\Payment;

use AdyenPayment\Components\Adyen\PaymentMethod\ImageLogoProviderInterface;
use AdyenPayment\Models\Payment\PaymentMethod;
use Shopware_Components_Snippet_Manager;

final class PaymentMethodEnricher implements PaymentMethodEnricherInterface
{
    /**
     * @var Shopware_Components_Snippet_Manager
     */
    private $snippets;

    /**
     * @var ImageLogoProviderInterface
     */
    private $imageLogoProvider;

    public function __construct(
        Shopware_Components_Snippet_Manager $snippets,
        ImageLogoProviderInterface $imageLogoProvider
    ) {
        $this->snippets = $snippets;
        $this->imageLogoProvider = $imageLogoProvider;
    }

    public function enrichPaymentMethod(array $shopwareMethod, PaymentMethod $paymentMethod): array
    {
        return array_merge($shopwareMethod, [
            'additionaldescription' => $this->enrichDescription($paymentMethod),
            'image' => $this->imageLogoProvider->provideByType($paymentMethod->getType()),
            'isStoredPayment' => $paymentMethod->isStoredPayment(),
            'isAdyenPaymentMethod' => true,
            'adyenType' => $shopwareMethod['attribute']['adyen_type'] ?? '',
            'metadata' => $paymentMethod->getRawData(),
        ]);
    }

    /**
     * @return string
     */
    private function enrichDescription(PaymentMethod $adyenMethod)
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
