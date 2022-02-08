<?php

declare(strict_types=1);

namespace AdyenPayment\Components\Adyen\PaymentMethod;

use AdyenPayment\Models\Payment\PaymentMean;
use Enlight_Controller_Request_Request;

interface StoredPaymentMeanProviderInterface
{
    public function fromRequest(Enlight_Controller_Request_Request $request): ?PaymentMean;
}
