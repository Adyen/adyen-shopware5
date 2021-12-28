<?php

declare(strict_types=1);

namespace AdyenPayment\Certificate\Mapper;

interface HttpCodeToLogLevelInterface
{
    public function __invoke(int $statusCode): int;
}
