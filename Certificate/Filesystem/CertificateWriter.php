<?php

declare(strict_types=1);

namespace AdyenPayment\Certificate\Filesystem;

use AdyenPayment\Certificate\Exception\CouldNotWriteCertificate;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Filesystem\Filesystem;

final class CertificateWriter implements CertificateWriterInterface
{
    public function __invoke(string $toDir, string $filename, string $content): void
    {
        $filesystem = new Filesystem();

        try {
            $filesystem->mkdir($toDir, 0700);
            $filesystem->dumpFile(
                $toDir.'/'.$filename,
                $content
            );
        } catch (IOExceptionInterface $exception) {
            throw CouldNotWriteCertificate::withFilepath($toDir.'/'.$filename);
        }
    }
}
