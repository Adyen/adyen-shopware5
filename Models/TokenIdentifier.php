<?php

declare(strict_types=1);

namespace AdyenPayment\Models;

use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

final class TokenIdentifier
{
    private UuidInterface $tokenId;

    private function __construct(UuidInterface $tokenId)
    {
        $this->tokenId = $tokenId;
    }

    public static function generate(): TokenIdentifier
    {
        return new self(Uuid::uuid4());
    }

    public static function generateFromString(string $uuid): TokenIdentifier
    {
        return new self(Uuid::fromString($uuid));
    }

    public function identifier(): string
    {
        return $this->tokenId->toString();
    }

    public function equals(TokenIdentifier $id): bool
    {
        return $id->identifier() === $this->identifier();
    }
}
