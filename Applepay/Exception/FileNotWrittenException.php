<?php

declare(strict_types=1);

namespace AdyenPayment\Applepay\Exception;

final class FileNotWrittenException extends \RuntimeException
{
    public static function withFilepath(string $filepath): self
    {
        return new self('Could not write apple pay association file, path: "'.$filepath.'"');
    }
}
