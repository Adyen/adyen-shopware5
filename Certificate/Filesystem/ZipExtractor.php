<?php

declare(strict_types=1);

namespace AdyenPayment\Certificate\Filesystem;

use AdyenPayment\Certificate\Exception\CouldNotWriteCertificate;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Filesystem\Filesystem;

final class ZipExtractor implements ZipExtractorInterface
{
    public function __invoke(string $fromDir, string $toDir, string $filename, string $extension): void
    {
        $filesystem = new Filesystem();

        try {
            $zip = new \ZipArchive();

            if (!$filesystem->exists($toDir)) {
                $filesystem->mkdir($toDir, 0700);
            }

            if ($zip->open($fromDir.'/'.$filename.$extension)) {
                $zip->extractTo($toDir);
                $zip->close();
            }
        } catch (IOExceptionInterface $exception) {
            throw CouldNotWriteCertificate::withFilepath($toDir.'/'.$filename.$extension);
        }
    }
}
