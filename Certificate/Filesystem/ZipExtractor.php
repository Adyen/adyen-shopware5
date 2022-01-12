<?php

declare(strict_types=1);

namespace AdyenPayment\Certificate\Filesystem;

use AdyenPayment\Certificate\Exception\CouldNotWriteCertificate;
use AdyenPayment\Certificate\Model\ApplePayCertificate;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Filesystem\Filesystem;

final class ZipExtractor implements ZipExtractorInterface
{
    private const ADYEN_APPLE_PAY_CERTIFICATE_FALLBACK_DIR = 'var/storage/apple/archive';
    private const ADYEN_APPLE_PAY_CERTIFICATE_DIR = '.well-known';
    private const ADYEN_APPLE_PAY_CERTIFICATE = 'apple-developer-merchantid-domain-association';
    private const ADYEN_APPLE_PAY_ZIP_EXTENSION = '.zip';

    /**
     * @throws CouldNotWriteCertificate
     *
     * @return ApplePayCertificate|void
     */
    public function __invoke()
    {
        $filesystem = new Filesystem();

        try {
            $zip = new \ZipArchive();

            if (!$filesystem->exists(self::ADYEN_APPLE_PAY_CERTIFICATE_DIR)) {
                $filesystem->mkdir(self::ADYEN_APPLE_PAY_CERTIFICATE_DIR, 0700);
            }

            if ($zip->open(
                self::ADYEN_APPLE_PAY_CERTIFICATE_FALLBACK_DIR.
                '/'.self::ADYEN_APPLE_PAY_CERTIFICATE.self::ADYEN_APPLE_PAY_ZIP_EXTENSION)) {
                $zip->extractTo(self::ADYEN_APPLE_PAY_CERTIFICATE_DIR);
                $zip->close();

                return ApplePayCertificate::create(
                    file_get_contents(
                        self::ADYEN_APPLE_PAY_CERTIFICATE_DIR.'/'.self::ADYEN_APPLE_PAY_CERTIFICATE
                    )
                );
            }
        } catch (IOExceptionInterface $exception) {
            throw CouldNotWriteCertificate::withFilepath(
                self::ADYEN_APPLE_PAY_CERTIFICATE_DIR.
                '/'.self::ADYEN_APPLE_PAY_CERTIFICATE.self::ADYEN_APPLE_PAY_ZIP_EXTENSION,
                $exception);
        }
    }
}
