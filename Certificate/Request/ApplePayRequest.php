<?php

declare(strict_types=1);

namespace AdyenPayment\Certificate\Request;

use Phpro\HttpTools\Request\RequestInterface;

class ApplePayRequest implements RequestInterface
{
    public function method(): string
    {
        return 'GET';
    }

    public function uri(): string
    {
        return '';
    }

    public function uriParameters(): array
    {
        return [];
    }

    public function body(): array
    {
        return [];
    }
}
