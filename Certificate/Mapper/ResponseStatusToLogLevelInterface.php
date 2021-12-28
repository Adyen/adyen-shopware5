<?php

declare(strict_types=1);

namespace AdyenPayment\Certificate\Mapper;

interface ResponseStatusToLogLevelInterface
{
    public function __invoke(string $responseBody): int;
}
