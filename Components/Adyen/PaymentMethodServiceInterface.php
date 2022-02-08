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
     * @internal
     */
    public function getCheckout(): Checkout;
}
