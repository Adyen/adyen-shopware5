<?php

declare(strict_types=1);

namespace AdyenPayment\Certificate\Response;

use AdyenPayment\Certificate\Model\ApplePayCertificate;

interface ApplePayCertificateHandlerInterface
{
    public function __invoke(string $response): ApplePayCertificate;
}
