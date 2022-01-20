<?php

declare(strict_types=1);

namespace AdyenPayment\Applepay\Exception;

final class FileNotDownloadedException extends \RuntimeException
{
    public static function fromException(\Throwable $exception): self
    {
        return new self(
            'Could not download Adyen ApplPay merchant id domain association file',
            $exception->getCode(),
            $exception
        );
    }
}
