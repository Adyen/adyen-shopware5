<?php

declare(strict_types=1);

namespace AdyenPayment\Certificate\Response;

use AdyenPayment\Certificate\Model\ApplePay;
use AdyenPayment\Certificate\Service\CertificateWriterInterface;
use AdyenPayment\Certificate\Service\ZipExtractorInterface;

final class ApplePayResponse
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

    public function createFromString(string $response): ApplePay
    {
        $adyenCertificate = ($this->certificateWriter)(
            self::ADYEN_APPLE_PAY_CERTIFICATE_DIR,
            self::ADYEN_APPLE_PAY_CERTIFICATE,
            $response
        );

        return ApplePay::create($adyenCertificate);
    }

    public function createFromFallbackZip(): ApplePay
    {
        $fallbackCertificate = ($this->zipExtractor)(
            self::ADYEN_APPLE_PAY_CERTIFICATE_FALLBACK_DIR,
            self::ADYEN_APPLE_PAY_CERTIFICATE_DIR,
            self::ADYEN_APPLE_PAY_CERTIFICATE,
            self::ADYEN_APPLE_PAY_ZIP_EXTENSION
        );
        $fallbackCertificate = !$fallbackCertificate ? '' : $fallbackCertificate;

        return ApplePay::create($fallbackCertificate);
    }
}
