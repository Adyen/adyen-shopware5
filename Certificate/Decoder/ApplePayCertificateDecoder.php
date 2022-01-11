<?php

declare(strict_types=1);

namespace AdyenPayment\Certificate\Decoder;

use AdyenPayment\Certificate\Filesystem\CertificateWriterInterface;
use AdyenPayment\Certificate\Filesystem\ZipExtractorInterface;
use AdyenPayment\Certificate\Response\ApplePayResponse;
use Phpro\HttpTools\Encoding\DecoderInterface;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\HttpFoundation\Response;

final class ApplePayCertificateDecoder implements DecoderInterface
{
    private ZipExtractorInterface $zipExtractor;
    private CertificateWriterInterface $certificateWriter;

    public function __construct(ZipExtractorInterface $zipExtractor, CertificateWriterInterface $certificateWriter)
    {
        $this->zipExtractor = $zipExtractor;
        $this->certificateWriter = $certificateWriter;
    }

    public function __invoke(ResponseInterface $response): void
    {
        $appleResponse = new ApplePayResponse($this->zipExtractor, $this->certificateWriter);

        $responseBody = $response->getBody()->getContents();

        if ('' === $responseBody || Response::HTTP_OK !== $response->getStatusCode()) {
            $appleResponse->createFromFallbackZip();
        }

        $appleResponse->createFromRaw($responseBody);
    }
}
