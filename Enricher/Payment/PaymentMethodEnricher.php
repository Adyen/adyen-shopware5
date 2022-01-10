<?php

declare(strict_types=1);

namespace AdyenPayment\Enricher\Payment;

use AdyenPayment\AdyenPayment;
use AdyenPayment\Components\Adyen\PaymentMethod\ImageLogoProviderInterface;
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
        /** @TODO - ASW-377: WIP, clean up. */
        $storedMethodUmbrellaId = $paymentMethod->isStoredPayment() ? sprintf(
            '%s_%s',
            $shopwareMethod['id'],
            $paymentMethod->getStoredPaymentMethodId()
        ) : null;

        return array_merge($shopwareMethod, [
            'enriched' => true,
            'additionaldescription' => $this->enrichAdditionalDescription($paymentMethod),
            'image' => $this->imageLogoProvider->provideByType($paymentMethod->adyenType()->type()),
            'isStoredPayment' => $paymentMethod->isStoredPayment(),
            'isAdyenPaymentMethod' => true,
            'adyenType' => $paymentMethod->adyenType()->type(),
            'metadata' => $paymentMethod->rawData(),
            'stored_method_umbrella_id' => $storedMethodUmbrellaId,
            'stored_method_id' => $paymentMethod->isStoredPayment() ? $paymentMethod->getStoredPaymentMethodId() : null,
        ],
            $paymentMethod->isStoredPayment() ? [
                'description' => $paymentMethod->getValue('name'),
                'attribute' => new Attribute([
                    AdyenPayment::ADYEN_STORED_METHOD_ID => $paymentMethod->getStoredPaymentMethodId(),
                ]),
            ] : []
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
}
