<?php

declare(strict_types=1);

namespace AdyenPayment\Serializer\Payment;

use AdyenPayment\Collection\Payment\PaymentMethodCollection;
use AdyenPayment\Components\Configuration;
use AdyenPayment\Components\PaymentMethodService as ShopwarePaymentMethodService;
use AdyenPayment\Models\Payment\PaymentMethod;
use AdyenPayment\Models\Payment\PaymentMethodType;
use AdyenPayment\Models\PaymentMethodInfo;
use Shopware_Components_Snippet_Manager;

final class PaymentMethodSerializer
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
     * @param array<int, array<string, mixed>> $shopwareMethods
     *
     * @return array<string, array<string, mixed>>
     */
    public function __invoke(array $shopwareMethods, PaymentMethodCollection $adyenPaymentMethods): array
    {
        $defaultPaymentMethods = $adyenPaymentMethods->filterByPaymentType(PaymentMethodType::default());
        $storedPaymentMethods = $adyenPaymentMethods->filterByPaymentType(PaymentMethodType::stored());

        return [
            'paymentMethods' => array_merge(
                $defaultPaymentMethods->map(
                    function (PaymentMethod $adyenMethod) use ($defaultPaymentMethods) {
                        return $this->serialize($adyenMethod, $defaultPaymentMethods);
                    }
                ),
                $shopwareMethods
            ),
            'storedPaymentMethods' => $storedPaymentMethods->map(
                function (PaymentMethod $adyenMethod) use ($storedPaymentMethods) {
                    return $this->serialize($adyenMethod, $storedPaymentMethods);
                }
            ),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function serialize(PaymentMethod $paymentMethod, PaymentMethodCollection $allAdyenMethods): array
    {
        $paymentMethodInfo = $this->paymentMethodService->getAdyenPaymentInfoByType(
            $paymentMethod->getType(),
            $allAdyenMethods->mapToRaw()
        );
        $paymentMethodInfo = $this->enrich($paymentMethodInfo, $paymentMethod);

        return [
            'id' => $this->provideId($paymentMethod),
            'name' => $paymentMethod->getType(),
            'description' => $paymentMethodInfo->getName(),
            'additionaldescription' => $paymentMethodInfo->getDescription(),
            'image' => $this->paymentMethodService->getAdyenImageByType($paymentMethod->getType()),
            'metadata' => $paymentMethod->getRawData(),
        ];
    }

    /**
     * Default payment methods do not have an id,  type is used
     * Stored payment methods have an id which is used
     */
    private function provideId(PaymentMethod $adyenMethod): string
    {
        return Configuration::PAYMENT_PREFIX.($adyenMethod->getId() ?: $adyenMethod->getType());
    }

    private function enrich(PaymentMethodInfo $paymentMethodInfo, PaymentMethod $adyenMethod): PaymentMethodInfo
    {
        if (!$adyenMethod->isStoredPayment()) {
            return $paymentMethodInfo;
        }

        $paymentMethodInfo->setDescription(sprintf(
            '%s%s: %s',
            ($paymentMethodInfo->getDescription() ? $paymentMethodInfo->getDescription().' ' : ''),
            $this->snippets
                ->getNamespace('adyen/checkout/payment')
                ->get('CardNumberEndingOn', 'Card number ending on', true),
            $adyenMethod->getValue('lastFour', '')
        ));

        return $paymentMethodInfo;
    }
}
