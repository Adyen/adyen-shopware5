<?php

declare(strict_types=1);

namespace AdyenPayment\Certificate\Mapper;

use Monolog\Logger;

final class ResponseStatusToLogLevel implements ResponseStatusToLogLevelInterface
{
    public function __invoke(string $responseBody): int
    {
        // TODO dump responseBody to see if other codes are possible and different log levels are neccessary
        if ('' === $responseBody) {
            return Logger::ERROR;
        }

        return Logger::DEBUG;
    }
}
