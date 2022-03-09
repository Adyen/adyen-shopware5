<?php

declare(strict_types=1);

namespace AdyenPayment\AdyenApi\Model;

final class ApiResponse
{
    private int $statusCode;
    private bool $success;
    private string $message;

    private function __construct(int $statusCode, bool $success, string $message)
    {
        $this->statusCode = $statusCode;
        $this->success = $success;
        $this->message = $message;
    }

    public static function create(int $statusCode, bool $success, string $message): self
    {
        return new self($statusCode, $success, $message);
    }

    public function statusCode(): int
    {
        return $this->statusCode;
    }

    public function isSuccess(): bool
    {
        return $this->success;
    }

    public function message(): string
    {
        return $this->message;
    }
}
