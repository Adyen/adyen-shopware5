<?php

namespace AdyenPayment\Exceptions;

/**
 * Class InvalidParameterException
 * @package AdyenPayment\Exceptions
 */
class InvalidParameterException extends \Exception
{
    public static function missingParameter(string $parameter): self
    {
        return new static("Missing parameter " . $parameter);
    }
}
