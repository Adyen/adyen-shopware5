<?php

declare(strict_types=1);

namespace AdyenPayment\Http\Logging;

use Psr\Http\Message\RequestInterface;

final class SimpleRequestFormatter implements RequestFormatter
{
    public function format(RequestInterface $request): string
    {
        return sprintf("Sending request:\n%s %s %s",
            $request->getMethod(),
            $request->getUri()->__toString(),
            $request->getProtocolVersion()
        );
    }
}
