<?php

declare(strict_types=1);

namespace AdyenPayment\Converter\Payment;

use AdyenPayment\Components\Configuration;
use AdyenPayment\Components\PaymentMethodService as ShopwarePaymentMethodService;
use AdyenPayment\Models\PaymentMethodInfo;
use Shopware_Components_Snippet_Manager;

final class PaymentMethodConverter
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

    /**
     * @param array<int, array<string, mixed>>    $shopwareMethods
     * @param array<string, array<string, mixed>> $adyenMethods
     *
     * @return array<string, array<string, mixed>>
     */
    public function toShopwarePaymentMethod(array $shopwareMethods, array $adyenMethods): array
    {
        $paymentMethods = $adyenMethods['paymentMethods'] ?? [];
        $storedPaymentMethods = $adyenMethods['storedPaymentMethods'] ?? [];

        return [
            'paymentMethods' => array_merge(array_reduce(
                $paymentMethods,
                function (array $payload, array $adyenMethod) use ($paymentMethods) {
                    $adyenMethod['paymentMethodType'] = PaymentMethodType::default();

                    return $this->convert($payload, $adyenMethod, $paymentMethods);
                },
                []
            ), $shopwareMethods),
            'storedPaymentMethods' => array_reduce(
                $storedPaymentMethods,
                function (array $payload, array $adyenMethod) use ($storedPaymentMethods) {
                    $adyenMethod['paymentMethodType'] = PaymentMethodType::stored();

                    return $this->convert($payload, $adyenMethod, $storedPaymentMethods);
                },
                []
            ),
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $payload
     * @param array<string, mixed>             $adyenMethod
     * @param array<int, array<string, mixed>> $allAdyenMethods
     *
     * @return array<int, array<string, mixed>>
     */
    private function convert(array $payload, array $adyenMethod, array $allAdyenMethods): array
    {
        $paymentMethodInfo = $this->paymentMethodService->getAdyenPaymentInfoByType(
            $adyenMethod['type'],
            $allAdyenMethods
        );
        $paymentMethodInfo = $this->enrich($paymentMethodInfo, $adyenMethod);

        $payload[] = [
            'id' => $this->provideId($adyenMethod),
            'name' => $adyenMethod['type'],
            'description' => $paymentMethodInfo->getName(),
            'additionaldescription' => $paymentMethodInfo->getDescription(),
            'image' => $this->paymentMethodService->getAdyenImage($adyenMethod),
            'metadata' => $adyenMethod,
        ];

        return $payload;
    }

    /**
     * @param array<string, mixed> $adyenMethod
     *
     * @return string
     */
    private function provideId(array $adyenMethod): string
    {
        return sprintf(
            '%s%s%s',
            Configuration::PAYMENT_PREFIX,
            $adyenMethod['type'],
            $adyenMethod['id'] ? '_'.$adyenMethod['id'] : ''
        );
    }

    /**
     * @param PaymentMethodInfo    $paymentMethodInfo
     * @param array<string, mixed> $adyenMethod
     *
     * @return PaymentMethodInfo
     */
    private function enrich(PaymentMethodInfo $paymentMethodInfo, array $adyenMethod): PaymentMethodInfo
    {
        $paymentMethodType = $adyenMethod['paymentMethodType'];
        if (!$paymentMethodType->equals(PaymentMethodType::stored())) {
            return $paymentMethodInfo;
        }

        $paymentMethodInfo->setDescription(sprintf(
            '%s%s: %s',
            ($paymentMethodInfo->getDescription() ? $paymentMethodInfo->getDescription().' ' : ''),
            $this->snippets
                ->getNamespace('adyen/checkout/payment')
                ->get('CardNumberEndingOn', 'Card number ending on', true),
            (string)($adyenMethod['lastFour'] ?? '')
        ));

        return $paymentMethodInfo;
    }
}
