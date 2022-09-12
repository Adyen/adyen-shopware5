<?php

declare(strict_types=1);

namespace AdyenPayment\AdyenApi\Model;

final class ApiResponse
{
    /** @var bool */
    private $success;

    /** @var string */
    private $message;

    private function __construct(bool $success, string $message)
    {
        $this->success = $success;
        $this->message = $message;
    }

    public static function create(bool $success, string $message): self
    {
        return new self($success, $message);
    }

    public static function empty(): self
    {
        return new self(false, 'Customer number not found.');
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
