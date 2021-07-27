<?php

declare(strict_types=1);

namespace AdyenPayment\Models\Enum\PaymentMethod;

use Assert\Assertion;

final class ImportStatus
{
    private static $CREATED = 'CREATED';
    private static $NOT_CHANGED = 'NOT_CHANGED';
    private static $NOT_IMPORTED = 'NOT_IMPORTED';

    /** @var string */
    private $status;

    public function __construct($status)
    {
        Assertion::string($status);

        if (!self::isStatusAllowed($status)) {
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

    public static function createdStatus()
    {
        return new self(self::$CREATED);
    }

    public static function notChangedStatus()
    {
        return new self(self::$NOT_CHANGED);
    }

    public static function notImportedStatus()
    {
        return new self(self::$NOT_IMPORTED);
    }

    public static function isStatusAllowed(string $status): bool
    {
        return in_array($status, self::availableStatuses(), true);
    }

    /**
     * @return string[]
     */
    public static function availableStatuses(): array
    {
        return [
            self::$CREATED,
            self::$NOT_CHANGED,
            self::$NOT_IMPORTED
        ];
    }
}
