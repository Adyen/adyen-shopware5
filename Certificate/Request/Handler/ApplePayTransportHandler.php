<?php

declare(strict_types=1);

namespace AdyenPayment\Certificate\Request\Handler;

use AdyenPayment\Certificate\Filesystem\ZipExtractorInterface;
use AdyenPayment\Certificate\Model\ApplePayCertificate;
use AdyenPayment\Certificate\Request\ApplePayCertificateRequest;
use AdyenPayment\Certificate\Response\ApplePayCertificateHandlerInterface;
use AdyenPayment\Certificate\Transport\StreamTransportHandler;
use Phpro\HttpTools\Transport\TransportInterface;
use Psr\Http\Message\StreamInterface;

final class ApplePayTransportHandler implements ApplePayTransportHandlerInterface
{
    private StreamTransportHandler $streamTransport;
    private ApplePayCertificateHandlerInterface $applePayCertificateHandler;
    private ZipExtractorInterface $zipExtractor;

    public function __construct(
        StreamTransportHandler $streamTransport,
        ApplePayCertificateHandlerInterface $applePayCertificateHandler,
        ZipExtractorInterface $zipExtractor
    ) {
        $this->streamTransport = $streamTransport;
        $this->applePayCertificateHandler = $applePayCertificateHandler;
        $this->zipExtractor = $zipExtractor;
    }

    public function __invoke(ApplePayCertificateRequest $applePayRequest): ApplePayCertificate
    {
        /** @var TransportInterface $transport */
        $transport = ($this->streamTransport)();

        /** @var StreamInterface $streamData */
        $streamData = ($transport)($applePayRequest);

        $streamDataContent = $streamData->getContents();

        // TODO: no check on response status code???
        if ('' === $streamDataContent) {
            return ($this->zipExtractor)();
        }

        return ($this->applePayCertificateHandler)($streamDataContent);
    }
}
