<?php

declare(strict_types=1);

namespace AdyenPayment\Http\Logging;

use Psr\Http\Message\ResponseInterface;

interface ResponseFormatter
{
    public function format(ResponseInterface $response): string;
}
