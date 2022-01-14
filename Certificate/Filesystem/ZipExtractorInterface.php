<?php

declare(strict_types=1);

namespace AdyenPayment\Certificate\Filesystem;

use AdyenPayment\Certificate\Exception\CouldNotWriteCertificate;

interface ZipExtractorInterface
{
    /**
     * @throws CouldNotWriteCertificate
     */
    public function __invoke(): void;
}
