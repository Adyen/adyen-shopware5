<?php

declare(strict_types=1);

namespace AdyenPayment\Certificate\Mapper;

use Monolog\Logger;

final class ResponseStatusToLogLevel implements ResponseStatusToLogLevelInterface
{
    public function __invoke(string $responseBody): int
    {
        if ('' === $responseBody) {
            return Logger::ERROR;
        }

        return Logger::DEBUG;
    }
}
