<?php

declare(strict_types=1);

namespace AdyenPayment\Certificate\Response;

use AdyenPayment\Certificate\Filesystem\CertificateWriterInterface;
use AdyenPayment\Certificate\Filesystem\ZipExtractorInterface;
use AdyenPayment\Certificate\Model\ApplePayCertificate;

final class ApplePayResponse implements ApplePayResponseInterface
{
    private ZipExtractorInterface $zipExtractor;
    private CertificateWriterInterface $certificateWriter;

    public function __construct(
        ZipExtractorInterface $zipExtractor,
        CertificateWriterInterface $certificateWriter
    ) {
        $this->zipExtractor = $zipExtractor;
        $this->certificateWriter = $certificateWriter;
    }

    public function createFromRaw(string $response): ApplePayCertificate
    {
        ($this->certificateWriter)($response);

        return ApplePayCertificate::create($response);
    }

    public function createFromFallbackZip(): void
    {
        ($this->zipExtractor)();
    }
}
