<?php

declare(strict_types=1);

namespace AdyenPayment\Models\Enum\PaymentMethod;

use Assert\Assertion;

final class ImportStatus
{
    private static $CREATED = 'CREATED';
    private static $NOT_CHANGED = 'NOT_CHANGED';
    private static $NOT_HANDLED = 'NOT_HANDLED';

    /** @var string */
    private $status;

    public function __construct(string $status)
    {
        if (!self::isValidStatus($status)) {
            throw new \InvalidArgumentException('Invalid import status: "' . $status . '"');
        }

        $this->status = $status;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function equals(ImportStatus $importStatus): bool
    {
        return $importStatus->getStatus() === $this->status;
    }

    public static function load(string $status): self
    {
        return new self($status);
    }

    public static function created()
    {
        return new self(self::$CREATED);
    }

    public static function notChanged()
    {
        return new self(self::$NOT_CHANGED);
    }

    public static function notHandledStatus()
    {
        return new self(self::$NOT_HANDLED);
    }

    public static function isValidStatus(string $status): bool
    {
        $availableStatuses = [
            self::$CREATED,
            self::$NOT_CHANGED,
            self::$NOT_HANDLED
        ];

        return in_array($status, $availableStatuses, true);
    }
}
