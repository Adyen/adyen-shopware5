<?php

declare(strict_types=1);

namespace AdyenPayment\Certificate\Model;

final class ApplePay
{
    private string $certificateString;

    private function __construct(string $certificateString)
    {
        $this->certificateString = $certificateString;
    }

    public static function create(string $certificateString): self
    {
        return new self($certificateString);
    }

    public function certificateString(): string
    {
        return $this->certificateString;
    }
}
