<?php

declare(strict_types=1);

namespace AdyenPayment\Exceptions;

class InvalidPaymentsResponseException extends \Exception
{
    public static function invalid(): self
    {
        return new static('Payments response not found.');
    }
}
