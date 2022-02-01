<?php

declare(strict_types=1);

namespace AdyenPayment\Http\Logging;

use Psr\Http\Message\RequestInterface;

interface RequestFormatter
{
    public function format(RequestInterface $request): string;
}
