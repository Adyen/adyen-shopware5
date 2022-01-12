<?php

declare(strict_types=1);

namespace AdyenPayment\Certificate\Request\Handler;

use AdyenPayment\Certificate\Request\ApplePayRequest;

interface ApplePayHandlerInterface
{
    public function __invoke(ApplePayRequest $applePayRequest): void;
}
