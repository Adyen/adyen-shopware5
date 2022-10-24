<?php

declare(strict_types=1);

namespace AdyenPayment\Exceptions;

class InvalidRequestPayloadException extends AuthorizationException
{
    public static function missingBody(): self
    {
        return new self('Missing notification request payload.');
    }

    public static function invalidBody(): self
    {
        return new self('Invalid notification request payload. The request body is not in a valid JSON format.');
    }
}
