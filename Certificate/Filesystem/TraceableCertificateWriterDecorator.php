<?php

declare(strict_types=1);

namespace AdyenPayment\Certificate\Filesystem;

use AdyenPayment\Certificate\Exception\CouldNotWriteCertificate;
use Psr\Log\LoggerInterface;

final class TraceableCertificateWriterDecorator implements CertificateWriterInterface
{
    private CertificateWriterInterface $certificateWriter;
    private LoggerInterface $logger;

    public function __construct(
        CertificateWriterInterface $certificateWriter,
        LoggerInterface $logger
    ) {
        $this->certificateWriter = $certificateWriter;
        $this->logger = $logger;
    }

    public function __invoke(string $content): void
    {
        try {
            ($this->certificateWriter)($content);
        } catch (CouldNotWriteCertificate $exception) {
            $this->logger->error($exception);
        }
    }
}
