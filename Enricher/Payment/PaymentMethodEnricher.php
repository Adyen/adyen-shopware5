<?php

declare(strict_types=1);

namespace AdyenPayment\Enricher\Payment;

use AdyenPayment\Components\Configuration;
use AdyenPayment\Components\PaymentMethodService as ShopwarePaymentMethodService;
use AdyenPayment\Models\Payment\PaymentMethod;
use Shopware_Components_Snippet_Manager;

final class PaymentMethodEnricher implements PaymentMethodEnricherInterface
{
    /**
     * @var ShopwarePaymentMethodService
     */
    private $paymentMethodService;
    /**
     * @var Shopware_Components_Snippet_Manager
     */
    private $snippets;

    public function __construct(
        ShopwarePaymentMethodService $paymentMethodService,
        Shopware_Components_Snippet_Manager $snippets
    ) {
        $this->paymentMethodService = $paymentMethodService;
        $this->snippets = $snippets;
    }

    public function enrichPaymentMethod(array $shopwareMethod, PaymentMethod $paymentMethod): array
    {
        return array_merge($shopwareMethod, [
            'additionaldescription' => $this->enrichDescription($paymentMethod),
            'image' => $this->paymentMethodService->getAdyenImageByType($paymentMethod->getType()),
            'isStoredPayment' => $paymentMethod->isStoredPayment(),
            'isAdyenPaymentMethod' => true,
            'adyenType' => $shopwareMethod['attribute']['adyen_type'] ?? '',
            'metadata' => $paymentMethod->getRawData()
        ]);
    }


    /**
     * @param PaymentMethod $adyenMethod
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
            ($description ? $description . ' ' : ''),
            $this->snippets
                ->getNamespace('adyen/checkout/payment')
                ->get('CardNumberEndingOn', 'Card number ending on', true),
            $adyenMethod->getValue('lastFour', '')
        );
    }
}
