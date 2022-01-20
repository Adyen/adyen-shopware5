<?php

declare(strict_types=1);

namespace AdyenPayment\Applepay\Exception;

final class ArchiveNotExtractedException extends \RuntimeException
{
    public static function fromPaths(string $archivePath, string $storagePath): self
    {
        return new self('Could not extract archive "'.$archivePath.'" to "'.$storagePath.'"');
    }
}
