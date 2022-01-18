<?php

declare(strict_types=1);

namespace AdyenPayment\Certificate\Filesystem;

use AdyenPayment\Certificate\Exception\CouldNotWriteCertificate;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Filesystem\Filesystem;

final class ZipExtractor implements ZipExtractorInterface
{
    private const BASE_PATH_PLUGIN = __DIR__.'/../../../../../custom/plugins/AdyenPayment/';
    private const ADYEN_APPLE_PAY_CERTIFICATE_FALLBACK_DIR = 'var/storage/apple/archive/';

    /**
     * @throws CouldNotWriteCertificate
     */
    public function __invoke(): void
    {
        $filesystem = new Filesystem();

        try {
            $zip = new \ZipArchive();

            if (!$filesystem->exists(self::BASE_PATH_PLUGIN.CertificateWriter::APPLE_PAY_CERTIFICATE_DIR)) {
                $filesystem->mkdir(self::BASE_PATH_PLUGIN.CertificateWriter::APPLE_PAY_CERTIFICATE_DIR, 0700);
            }

            if ($zip->open(
                self::BASE_PATH_PLUGIN.
                self::ADYEN_APPLE_PAY_CERTIFICATE_FALLBACK_DIR.
                '/'.CertificateWriter::APPLE_PAY_CERTIFICATE.CertificateWriter::ADYEN_APPLE_PAY_ZIP_EXTENSION)
            ) {
                $zip->extractTo(self::BASE_PATH_PLUGIN.CertificateWriter::APPLE_PAY_CERTIFICATE_DIR);
                $zip->close();
            }
        } catch (IOExceptionInterface $exception) {
            throw CouldNotWriteCertificate::withFilepath(
                self::BASE_PATH_PLUGIN.
                CertificateWriter::APPLE_PAY_CERTIFICATE_DIR.
                '/'.CertificateWriter::APPLE_PAY_CERTIFICATE.CertificateWriter::ADYEN_APPLE_PAY_ZIP_EXTENSION,
                $exception
            );
        }
    }
}
