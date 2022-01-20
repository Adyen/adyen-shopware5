<?php

declare(strict_types=1);

namespace AdyenPayment\Applepay\Exception;

final class ArchiveNotAccessibleException extends \RuntimeException
{
    public static function fromPath(string $archivePath): self
    {
        return new self('Could not open "'.$archivePath.'"');
    }
}
