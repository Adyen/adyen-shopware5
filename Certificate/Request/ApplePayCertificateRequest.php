<?php

declare(strict_types=1);

namespace AdyenPayment\Certificate\Request;

use Phpro\HttpTools\Request\RequestInterface;

/** @psalm-immutable */
final class ApplePayCertificateRequest implements RequestInterface
{
    private function __construct()
    {
    }

    public static function create(): self
    {
        return new self();
    }

    public function method(): string
    {
        return 'GET';
    }

    public function uri(): string
    {
        return '/.well-known/apple-developer-merchantid-domain-association';
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
