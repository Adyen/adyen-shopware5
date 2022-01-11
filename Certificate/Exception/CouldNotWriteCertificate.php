<?php

declare(strict_types=1);

namespace AdyenPayment\Certificate\Exception;

final class CouldNotWriteCertificate extends \RuntimeException
{
    public static function withFilepath(string $filepath): self
    {
        return new self(
            sprintf(
            'Could not write certificate to %s',
            $filepath
        ));
    }
}
