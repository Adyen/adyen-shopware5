<?php

declare(strict_types=1);

namespace AdyenPayment\Session;

interface MessageProvider
{
    public function hasMessages(): bool;

    public function add(string ...$messages): void;

    /**
     * Destructive read
     * @return array<string>
     */
    public function read(): array;
}
