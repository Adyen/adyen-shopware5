<?php

declare(strict_types=1);

namespace AdyenPayment\Certificate\Middleware;

interface MiddlewareInterface
{
    public function __invoke(callable $nextHandler): callable;
}
