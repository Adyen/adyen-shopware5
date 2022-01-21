<?php

declare(strict_types=1);

namespace AdyenPayment\Applepay\Exception;

final class ArchiveNotAccessibleException extends \RuntimeException
{
    public static function fromErrorCode(int $errorCode, string $archivePath): self
    {
        return new self('Could not open "'.$archivePath.'", ZipArchive error code: "'.$errorCode.'"');
    }
}
