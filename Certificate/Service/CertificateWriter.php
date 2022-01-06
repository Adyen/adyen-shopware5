<?php

declare(strict_types=1);

namespace AdyenPayment\Certificate\Service;

use Psr\Log\LoggerInterface;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Filesystem\Filesystem;

final class CertificateWriter implements CertificateWriterInterface
{
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function __invoke(string $toDir, string $filename, string $content): string
    {
        $filesystem = new Filesystem();

        try {
            $filesystem->mkdir($toDir, 0700);
            $filesystem->dumpFile(
                $toDir.'/'.$filename,
                $content
            );

            return $content;
        } catch (IOException|IOExceptionInterface $exception) {
            $this->logger->info($exception->getMessage());

            return '';
        }
    }
}
