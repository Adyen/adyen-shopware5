<?php

namespace MeteorAdyen\Exceptions;

/**
 * Class InvalidParameterException
 * @package MeteorAdyen\Exceptions
 */
class InvalidParameterException extends \Exception
{
    public static function missingParameter(string $parameter): self
    {
        return new static("Missing parameter " . $parameter);
    }
}
