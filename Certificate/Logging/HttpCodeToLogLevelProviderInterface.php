<?php

declare(strict_types=1);

namespace AdyenPayment\Certificate\Logging;

interface HttpCodeToLogLevelProviderInterface
{
    public function __invoke(int $statusCode): int;
}
