<?php

declare(strict_types=1);

namespace AdyenPayment\Exceptions;

use Adyen\AdyenException;

class InvalidHmacException extends \InvalidArgumentException
{
    public static function withHmacKey(string $hmac): self
    {
        return new self('Invalid notification HMAC detected "'.$hmac.'"');
    }

    public static function fromAdyenException(AdyenException $exception): self
    {
        return new self($exception->getMessage(), $exception->getCode(), $exception);
    }
}
