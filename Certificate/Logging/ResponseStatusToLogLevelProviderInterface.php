<?php

declare(strict_types=1);

namespace AdyenPayment\Certificate\Logging;

interface ResponseStatusToLogLevelProviderInterface
{
    public function __invoke(string $responseBody): int;
}
