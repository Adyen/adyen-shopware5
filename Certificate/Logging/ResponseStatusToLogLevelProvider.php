<?php

declare(strict_types=1);

namespace AdyenPayment\Certificate\Logging;

use Monolog\Logger;

final class ResponseStatusToLogLevelProvider implements ResponseStatusToLogLevelProviderInterface
{
    public function __invoke(string $responseBody): int
    {
        if ('' === $responseBody) {
            return Logger::ERROR;
        }

        return Logger::DEBUG;
    }
}
