<?php

declare(strict_types=1);

namespace AdyenPayment\Components\Adyen;

interface PaymentResultCodeHandlerInterface
{
    public function __invoke(array $paymentResponseInfo): void;
}
