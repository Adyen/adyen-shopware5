<?php

declare(strict_types=1);

namespace AdyenPayment\Certificate\Response;

use AdyenPayment\Certificate\Filesystem\CertificateWriterInterface;
use AdyenPayment\Certificate\Filesystem\ZipExtractorInterface;
use AdyenPayment\Certificate\Model\ApplePay;

final class ApplePayResponse implements ApplePayResponseInterface
{
    private ZipExtractorInterface $zipExtractor;
    private CertificateWriterInterface $certificateWriter;
    private const ADYEN_APPLE_PAY_CERTIFICATE_FALLBACK_DIR = 'var/storage/apple/archive';
    private const ADYEN_APPLE_PAY_CERTIFICATE_DIR = '.well-known';
    private const ADYEN_APPLE_PAY_CERTIFICATE = 'apple-developer-merchantid-domain-association';
    private const ADYEN_APPLE_PAY_ZIP_EXTENSION = '.zip';

    public function __construct(
        ZipExtractorInterface $zipExtractor,
        CertificateWriterInterface $certificateWriter
    ) {
        $this->zipExtractor = $zipExtractor;
        $this->certificateWriter = $certificateWriter;
    }

    public function createFromRaw(string $response): ApplePay
    {
        ($this->certificateWriter)($response);

        return ApplePay::create($response);
    }

    public function createFromFallbackZip(): void
    {
        ($this->zipExtractor)(
            self::ADYEN_APPLE_PAY_CERTIFICATE_FALLBACK_DIR,
            self::ADYEN_APPLE_PAY_CERTIFICATE_DIR,
            self::ADYEN_APPLE_PAY_CERTIFICATE,
            self::ADYEN_APPLE_PAY_ZIP_EXTENSION
        );
    }
}
