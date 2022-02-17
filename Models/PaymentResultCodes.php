<?php

declare(strict_types=1);

namespace AdyenPayment\Models;

final class PaymentResultCodes
{
    private const AUTHORISED = 'Authorised';
    private const CANCELLED = 'Cancelled';
    private const CHALLENGESHOPPER = 'ChallengeShopper';
    private const ERROR = 'Error';
    private const IDENTIFYSHOPPER = 'IdentifyShopper';
    private const INVALID = 'Invalid';
    private const PENDING = 'Pending';
    private const RECEIVED = 'Received';
    private const REDIRECTSHOPPER = 'RedirectShopper';
    private const REFUSED = 'Refused';
    private string $resultCode;

    private function __construct(string $resultCode)
    {
        if (!in_array($resultCode, $this->availableResultCodes(), true)) {
            throw new \InvalidArgumentException('Invalid result code: "'.$resultCode.'"');
        }

        $this->resultCode = $resultCode;
    }

    public function resultCode(): string
    {
        return $this->resultCode;
    }

    public function equals(PaymentResultCodes $paymentResultCodes): bool
    {
        return $paymentResultCodes->resultCode() === $this->resultCode;
    }

    public static function load(string $resultCode): self
    {
        return new self($resultCode);
    }

    public static function authorised(): self
    {
        return new self(self::AUTHORISED);
    }

    public static function challengeShopper(): self
    {
        return new self(self::CHALLENGESHOPPER);
    }

    public static function cancelled(): self
    {
        return new self(self::CANCELLED);
    }

    public static function error(): self
    {
        return new self(self::ERROR);
    }

    public static function invalid(): self
    {
        return new self(self::INVALID);
    }

    public static function identifyShopper(): self
    {
        return new self(self::IDENTIFYSHOPPER);
    }

    public static function pending(): self
    {
        return new self(self::PENDING);
    }

    public static function received(): self
    {
        return new self(self::RECEIVED);
    }

    public static function redirectShopper(): self
    {
        return new self(self::REDIRECTSHOPPER);
    }

    public static function refused(): self
    {
        return new self(self::REFUSED);
    }

    /**
     * @return array<string>
     */
    private function availableResultCodes(): array
    {
        return [
            self::AUTHORISED,
            self::CANCELLED,
            self::CHALLENGESHOPPER,
            self::ERROR,
            self::INVALID,
            self::IDENTIFYSHOPPER,
            self::PENDING,
            self::RECEIVED,
            self::REDIRECTSHOPPER,
            self::REFUSED,
        ];
    }
}
