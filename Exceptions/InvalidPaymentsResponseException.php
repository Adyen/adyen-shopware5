<?php

declare(strict_types=1);

namespace AdyenPayment\Exceptions;

class InvalidPaymentsResponseException extends \Exception
{
    public static function empty(): self
    {
        return new static('Empty Payment data.');
    }
}
