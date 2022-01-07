<?php

declare(strict_types=1);

namespace AdyenPayment\Certificate\Service;

use Psr\Log\LoggerInterface;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Filesystem\Filesystem;

final class ZipExtractor implements ZipExtractorInterface
{
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function __invoke(string $fromDir, string $toDir, string $filename, string $extension): string
    {
        $filesystem = new Filesystem();

        try {
            $zip = new \ZipArchive();

            if (!$filesystem->exists($fromDir)) {
                $this->logger->error('Could not find Apple Pay certificate zip from archive directory.');
            }

            if (!$filesystem->exists($toDir)) {
                $filesystem->mkdir($toDir, 0700);
            }

            if ($zip->open($fromDir.'/'.$filename.$extension)) {
                $zip->extractTo($toDir);
                $zip->close();

                return file_get_contents($toDir.'/'.$filename);
            }

            $this->logger->error('Extracting zip of Adyen Apple Pay certificate failed.');
        } catch (IOException|IOExceptionInterface $exception) {
            $this->logger->error($exception->getMessage());
        }

        return '';
    }
}
