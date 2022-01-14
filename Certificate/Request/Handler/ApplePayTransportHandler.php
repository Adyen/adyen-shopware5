<?php

declare(strict_types=1);

namespace AdyenPayment\Certificate\Request\Handler;

use AdyenPayment\Certificate\Filesystem\CertificateWriterInterface;
use AdyenPayment\Certificate\Filesystem\ZipExtractorInterface;
use AdyenPayment\Certificate\Request\ApplePayCertificateRequest;
use AdyenPayment\Certificate\Transport\StreamTransportHandlerInterface;
use Phpro\HttpTools\Transport\TransportInterface;
use Psr\Http\Message\StreamInterface;

final class ApplePayTransportHandler implements ApplePayTransportHandlerInterface
{
    private StreamTransportHandlerInterface $streamTransport;
    private CertificateWriterInterface $certificateWriter;
    private ZipExtractorInterface $zipExtractor;

    public function __construct(
        StreamTransportHandlerInterface $streamTransport,
        CertificateWriterInterface $certificateWriter,
        ZipExtractorInterface $zipExtractor
    ) {
        $this->streamTransport = $streamTransport;
        $this->certificateWriter = $certificateWriter;
        $this->zipExtractor = $zipExtractor;
    }

    public function __invoke(ApplePayCertificateRequest $applePayRequest): void
    {
        /** @var TransportInterface $transport */
        $transport = ($this->streamTransport)();

        /** @var StreamInterface $streamData */
        $streamData = ($transport)($applePayRequest);

        $streamDataContent = $streamData->getContents();

        if ('' !== $streamDataContent) {
            ($this->certificateWriter)($streamDataContent);
        }

        ($this->zipExtractor)();
    }
}
