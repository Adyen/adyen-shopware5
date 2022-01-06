<?php

declare(strict_types=1);

namespace AdyenPayment\Certificate\Service;

interface CertificateWriterInterface
{
    public function __invoke(string $toDir, string $filename, string $content): string;
}
