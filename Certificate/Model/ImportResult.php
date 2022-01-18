<?php

declare(strict_types=1);

namespace AdyenPayment\Certificate\Model;

final class ImportResult
{
    private bool $usedFallback;

    public static function success(): self
    {
        $new = new self();
        $new->usedFallback = false;

        return $new;
    }

    public static function successFallbackCertificate(): self
    {
        $new = new self();
        $new->usedFallback = true;

        return $new;
    }

    public function usedFallback(): bool
    {
        return $this->usedFallback;
    }
}
