<?php

declare(strict_types=1);

namespace AdyenPayment\Certificate\Request\Handler;

use AdyenPayment\Certificate\Decoder\ApplePayCertificateDecoder;
use AdyenPayment\Certificate\Encoder\ApplePayCertificateEncoder;
use AdyenPayment\Certificate\Model\ApplePay;
use AdyenPayment\Certificate\Request\ApplePayRequest;
use AdyenPayment\Certificate\Transport\StreamTransportFactory;

final class ApplePayHandler implements ApplePayHandlerInterface
{
    private StreamTransportFactory $streamTransport;
    private ApplePayCertificateEncoder $encoder;
    private ApplePayCertificateDecoder $decoder;

    public function __construct(
        StreamTransportFactory $streamTransport,
        ApplePayCertificateEncoder $applePayCertificateEncoder,
        ApplePayCertificateDecoder $applePayCertificateDecoder
    ) {
        $this->streamTransport = $streamTransport;
        $this->encoder = $applePayCertificateEncoder;
        $this->decoder = $applePayCertificateDecoder;
    }

    public function __invoke(ApplePayRequest $applePayRequest): ApplePay
    {
        /** @var ApplePayHandler $transport */
        $transport = $this->streamTransport->create(
            $this->encoder,
            $this->decoder
        );

        return ($transport)($applePayRequest);
    }
}
