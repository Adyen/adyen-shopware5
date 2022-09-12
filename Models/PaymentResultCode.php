<?php

declare(strict_types=1);

namespace AdyenPayment\Models;

final class PaymentResultCode
{
    private const AUTHORISED = 'Authorised';
    private const CANCELLED = 'Cancelled';
    private const CHALLENGE_SHOPPER = 'ChallengeShopper';
    private const ERROR = 'Error';
    private const IDENTIFY_SHOPPER = 'IdentifyShopper';
    private const INVALID = 'Invalid';
    private const PENDING = 'Pending';
    private const RECEIVED = 'Received';
    private const REDIRECT_SHOPPER = 'RedirectShopper';
    private const REFUSED = 'Refused';

    /** @var string */
    private $resultCode;

    private function __construct(string $resultCode)
    {
        if (!self::exists($resultCode)) {
            throw new \InvalidArgumentException('Invalid result code: "'.$resultCode.'"');
        }

        $this->resultCode = $resultCode;
    }

    public function resultCode(): string
    {
        return $this->resultCode;
    }

    public function equals(PaymentResultCode $paymentResultCode): bool
    {
        return $paymentResultCode->resultCode() === $this->resultCode;
    }

    public static function load(string $resultCode): self
    {
        return new self($resultCode);
    }

    public static function exists(string $resultCode): bool
    {
        return in_array($resultCode, self::availableResultCodes(), true);
    }

    public static function authorised(): self
    {
        return new self(self::AUTHORISED);
    }

    public static function challengeShopper(): self
    {
        return new self(self::CHALLENGE_SHOPPER);
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
        return new self(self::IDENTIFY_SHOPPER);
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
        return new self(self::REDIRECT_SHOPPER);
    }

    public static function refused(): self
    {
        return new self(self::REFUSED);
    }

    /**
     * @return array<string>
     */
    private static function availableResultCodes(): array
    {
        return [
            self::AUTHORISED,
            self::CANCELLED,
            self::CHALLENGE_SHOPPER,
            self::ERROR,
            self::INVALID,
            self::IDENTIFY_SHOPPER,
            self::PENDING,
            self::RECEIVED,
            self::REDIRECT_SHOPPER,
            self::REFUSED,
        ];
    }
}
