<?php

declare(strict_types=1);

namespace AdyenPayment\Components\Adyen;

use Adyen\Service\Checkout;
use AdyenPayment\Collection\Payment\PaymentMethodCollection;

interface PaymentMethodServiceInterface
{
    public function getPaymentMethods(
        ?string $countryCode = null,
        ?string $currency = null,
        ?float $value = null,
        ?string $locale = null,
        bool $cache = true
    ): PaymentMethodCollection;

    /**
     * @deprecated
     * @see \AdyenPayment\AdyenApi\TransportFactory::checkout()
     */
    public function getCheckout(): Checkout;

    /** @deprecated */
    public function provideCustomerNumber(): string;
}
