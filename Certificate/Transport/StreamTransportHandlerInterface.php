<?php

declare(strict_types=1);

namespace AdyenPayment\Certificate\Transport;

use Phpro\HttpTools\Transport\TransportInterface;

interface StreamTransportHandlerInterface
{
    public function __invoke(): TransportInterface;
}
