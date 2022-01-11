<?php

declare(strict_types=1);

namespace AdyenPayment\Certificate\Exception;

final class CouldNotWriteCertificate extends \RuntimeException
{
    public static function withFilepath(string $filepath, \Throwable $previous): self
    {
        return new self('Could not write certificate to "'.$filepath.'"', $previous->getCode(), $previous);
    }
}
