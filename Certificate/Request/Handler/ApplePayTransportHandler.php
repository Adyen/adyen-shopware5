<?php

declare(strict_types=1);

namespace AdyenPayment\Certificate\Request\Handler;

use AdyenPayment\Certificate\Filesystem\CertificateWriterInterface;
use AdyenPayment\Certificate\Filesystem\ZipExtractorInterface;
use AdyenPayment\Certificate\Request\ApplePayCertificateRequest;
use Phpro\HttpTools\Transport\TransportInterface;
use Psr\Http\Message\StreamInterface;

final class ApplePayTransportHandler implements ApplePayTransportHandlerInterface
{
    private TransportInterface $streamTransport;
    private CertificateWriterInterface $certificateWriter;
    private ZipExtractorInterface $zipExtractor;

    public function __construct(
        TransportInterface $streamTransport,
        CertificateWriterInterface $certificateWriter,
        ZipExtractorInterface $zipExtractor
    ) {
        $this->streamTransport = $streamTransport;
        $this->certificateWriter = $certificateWriter;
        $this->zipExtractor = $zipExtractor;
    }

    public function __invoke(ApplePayCertificateRequest $applePayRequest): void
    {
        /** @var StreamInterface $streamData */
        $streamData = ($this->streamTransport)($applePayRequest);

        $streamDataContent = $streamData->getContents();

        if ('' !== $streamDataContent) {
            ($this->certificateWriter)($streamDataContent);
        }

        ($this->zipExtractor)();
    }
}
