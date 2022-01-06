<?php

declare(strict_types=1);

namespace AdyenPayment\Certificate\Service;

interface ZipExtractorInterface
{
    public function __invoke(string $fromDir, string $toDir, string $filename, string $extension): string;
}
