<?php

declare(strict_types=1);

namespace AdyenPayment\Certificate\Request\Handler;

use AdyenPayment\Certificate\Model\ApplePay;
use AdyenPayment\Certificate\Request\ApplePayRequest;
use AdyenPayment\Certificate\Transport\StreamTransportFactory;
use Phpro\HttpTools\Encoding\DecoderInterface;
use Phpro\HttpTools\Encoding\EncoderInterface;

final class ApplePayHandler implements ApplePayHandlerInterface
{
    private StreamTransportFactory $streamTransport;
    private EncoderInterface $encoder;
    private DecoderInterface $decoder;

    public function __construct(
        StreamTransportFactory $streamTransport,
        EncoderInterface $applePayCertificateEncoder,
        DecoderInterface $applePayCertificateDecoder
    ) {
        $this->streamTransport = $streamTransport;
        $this->encoder = $applePayCertificateEncoder;
        $this->decoder = $applePayCertificateDecoder;
    }

    public function __invoke(ApplePayRequest $applePayRequest): ApplePay
    {
        $transport = $this->streamTransport->create(
            $this->encoder,
            $this->decoder
        );

        return ($transport)($applePayRequest);
    }
}
