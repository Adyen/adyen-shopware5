<?php

declare(strict_types=1);

namespace AdyenPayment\Subscriber\Checkout;

use AdyenPayment\Components\WebComponents\ConfigProvider;
use AdyenPayment\Models\Payment\PaymentType;

/**
 * Depends on EnrichUserAdditionalPaymentSubscriber.
 */
final class AddApplePayConfigToViewSubscriber extends BaseAddPaymentMethodConfigToViewSubscriber
{
    public function __construct(ConfigProvider $applePayConfigProvider)
    {
        parent::__construct($applePayConfigProvider);

        $this->paymentType = PaymentType::applePay();
        $this->paymentConfigViewKey = 'sAdyenApplePayConfig';
    }
}
