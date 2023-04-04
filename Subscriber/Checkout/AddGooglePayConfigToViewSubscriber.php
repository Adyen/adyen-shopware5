<?php

declare(strict_types=1);

namespace AdyenPayment\Subscriber\Checkout;

use AdyenPayment\Components\WebComponents\ConfigProvider;
use AdyenPayment\Models\Payment\PaymentType;

/**
 * Depends on EnrichUserAdditionalPaymentSubscriber.
 */
final class AddGooglePayConfigToViewSubscriber extends BaseAddPaymentMethodConfigToViewSubscriber
{
    public function __construct(ConfigProvider $googlePayConfigProvider)
    {
        parent::__construct($googlePayConfigProvider);

        $this->paymentType = PaymentType::googlePay();
        $this->paymentConfigViewKey = 'sAdyenGoogleConfig';
    }
}
