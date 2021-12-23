<?php

declare(strict_types=1);

namespace AdyenPayment\Exceptions;

final class PaymentExistsException extends \RuntimeException
{
    public static function withName(string $name): self
    {
        return new self('Payment with name "'.$name.'" already exists');
    }
}
