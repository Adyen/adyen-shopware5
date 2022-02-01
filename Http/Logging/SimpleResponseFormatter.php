<?php

declare(strict_types=1);

namespace AdyenPayment\Http\Logging;

use Psr\Http\Message\ResponseInterface;

final class SimpleResponseFormatter implements ResponseFormatter
{
    public function format(ResponseInterface $response): string
    {
        return sprintf(
            '%s %s %s',
            $response->getStatusCode(),
            $response->getReasonPhrase(),
            $response->getProtocolVersion()
        );
    }
}
