<?php

declare(strict_types=1);

namespace AdyenPayment\Certificate\Decoder;

use AdyenPayment\Certificate\Response\ApplePayResponse;
use Phpro\HttpTools\Encoding\DecoderInterface;
use Psr\Http\Message\ResponseInterface;

class ApplePayCertificateDecoder implements DecoderInterface
{
    public function __invoke(ResponseInterface $response): ApplePayResponse
    {
        $responseBody = $response->getBody()->getContents();

        if ('' === $responseBody) {
            throw new \InvalidArgumentException('Empty body/zip content passed.');
        }

        return ApplePayResponse::createFromZip($responseBody);
    }
}
