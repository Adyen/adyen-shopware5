<?php

declare(strict_types=1);

namespace AdyenPayment\Certificate\Request\Handler;

use AdyenPayment\Certificate\Request\ApplePayCertificateRequest;

interface ApplePayHandlerInterface
{
    public function __invoke(ApplePayCertificateRequest $applePayRequest): void;
}
