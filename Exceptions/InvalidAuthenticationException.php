<?php

declare(strict_types=1);

namespace AdyenPayment\Exceptions;

class InvalidAuthenticationException extends \InvalidArgumentException
{
    public static function missingAuthentication(): self
    {
        return new self('Missing notification authentication credentials');
    }

    public static function invalidCredentials(): self
    {
        return new self('Invalid notification authentication credentials');
    }
}
