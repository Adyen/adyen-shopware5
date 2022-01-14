<?php

declare(strict_types=1);

namespace AdyenPayment\Certificate\Exception;

final class CouldNotReadCertificate extends \RunTimeException
{
    public static function withFilepath(string $filepath): self
    {
        return new self('Could not read certificate from "'.$filepath.'"');
    }
}
