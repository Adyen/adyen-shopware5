<?php

declare(strict_types=1);

namespace AdyenPayment\Certificate\Filesystem;

interface CertificateWriterInterface
{
    public function __invoke(string $content): void;
}
