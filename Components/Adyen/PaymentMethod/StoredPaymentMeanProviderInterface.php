<?php

declare(strict_types=1);

namespace AdyenPayment\Components\Adyen\PaymentMethod;

use Enlight_Controller_Request_Request;

interface StoredPaymentMeanProviderInterface
{
    public function fromRequest(Enlight_Controller_Request_Request $request): ?string;
}
