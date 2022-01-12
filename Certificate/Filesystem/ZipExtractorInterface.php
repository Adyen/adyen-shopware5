<?php

declare(strict_types=1);

namespace AdyenPayment\Certificate\Filesystem;

interface ZipExtractorInterface
{
    public function __invoke(): void;
}
