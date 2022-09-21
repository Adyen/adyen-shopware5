<?php

declare(strict_types=1);

namespace AdyenPayment\Applepay\Exception;

use Shopware\Components\HttpClient\Response;

final class FileNotDownloadedException extends \RuntimeException
{
    public static function fromException(\Throwable $exception): self
    {
        return new self(
            'Could not download Adyen ApplePay merchant id domain association file',
            $exception->getCode(),
            $exception
        );
    }

    public static function fromResponse(Response $response): self
    {
        return new self(
            'Could not download Adyen ApplePay merchant id domain association file. The returned download response ' .
            'is with status ' . $response->getStatusCode(),
            $response->getStatusCode()
        );
    }
}
