<?php

declare(strict_types=1);

namespace AdyenPayment\Certificate\Encoder;

use Phpro\HttpTools\Encoding\EncoderInterface;
use Psr\Http\Message\RequestInterface;

final class PlainTextEncoder implements EncoderInterface
{
    public function __invoke(RequestInterface $request, $data): RequestInterface
    {
        return $request
            ->withAddedHeader('Content-Type', 'text/plain');
    }
}
