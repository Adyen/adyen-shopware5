<?php

declare(strict_types=1);

namespace AdyenPayment\Exceptions;

/**
 * Class InvalidParameterException.
 */
class InvalidParameterException extends \Exception
{
    public static function missingParameter(string $parameter): self
    {
        return new static('Missing parameter '.$parameter);
    }
}
