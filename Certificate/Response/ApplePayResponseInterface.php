<?php

declare(strict_types=1);

namespace AdyenPayment\Certificate\Response;

use AdyenPayment\Certificate\Model\ApplePay;

interface ApplePayResponseInterface
{
    public function createFromRaw(string $response): ApplePay;
    public function createFromFallbackZip(): void;
}
