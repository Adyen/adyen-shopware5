<?php

declare(strict_types=1);

namespace AdyenPayment\Certificate\Response;

use AdyenPayment\Certificate\Model\ApplePayCertificate;

interface ApplePayResponseInterface
{
    public function createFromRaw(string $response): ApplePayCertificate;
    public function createFromFallbackZip(): void;
}
