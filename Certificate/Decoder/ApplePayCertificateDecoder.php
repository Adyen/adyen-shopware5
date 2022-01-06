<?php

declare(strict_types=1);

namespace AdyenPayment\Certificate\Decoder;

use AdyenPayment\Certificate\Model\ApplePay;
use AdyenPayment\Certificate\Response\ApplePayResponse;
use AdyenPayment\Certificate\Service\CertificateWriterInterface;
use AdyenPayment\Certificate\Service\ZipExtractorInterface;
use Phpro\HttpTools\Encoding\DecoderInterface;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\HttpFoundation\Response;

class ApplePayCertificateDecoder implements DecoderInterface
{
    private ZipExtractorInterface $zipExtractor;
    private CertificateWriterInterface $certificateWriter;

    public function __construct(ZipExtractorInterface $zipExtractor, CertificateWriterInterface $certificateWriter)
    {
        $this->zipExtractor = $zipExtractor;
        $this->certificateWriter = $certificateWriter;
    }

    public function __invoke(ResponseInterface $response): ApplePay
    {
        $appleResponse = new ApplePayResponse($this->zipExtractor, $this->certificateWriter);

        $responseBody = $response->getBody()->getContents();

        if ('' === $responseBody || Response::HTTP_OK !== $response->getStatusCode()) {
            return $appleResponse->createFromFallbackZip();
        }

        return $appleResponse->createFromString($responseBody);
    }
}
