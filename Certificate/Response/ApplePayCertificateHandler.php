<?php

declare(strict_types=1);

namespace AdyenPayment\Certificate\Response;

use AdyenPayment\Certificate\Filesystem\CertificateWriterInterface;
use AdyenPayment\Certificate\Model\ApplePayCertificate;

final class ApplePayCertificateHandler implements ApplePayCertificateHandlerInterface
{
    private CertificateWriterInterface $certificateWriter;

    public function __construct(CertificateWriterInterface $certificateWriter)
    {
        $this->certificateWriter = $certificateWriter;
    }

    public function __invoke(string $response): ApplePayCertificate
    {
        ($this->certificateWriter)($response);

        return ApplePayCertificate::create($response);
    }
}
