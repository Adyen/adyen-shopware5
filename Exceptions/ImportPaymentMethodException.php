<?php

declare(strict_types=1);

namespace AdyenPayment\Exceptions;

class ImportPaymentMethodException extends \Exception
{
    public static function missingId(): self
    {
        return new static("Could not import payment method from adyen.");
    }
}
