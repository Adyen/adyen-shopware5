<?php

declare(strict_types=1);

namespace AdyenPayment\Models\Enum\PaymentMethod;

final class ImportStatus
{
    private static $CREATED = 'created';
    private static $NOT_CHANGED = 'not_changed';

    /** @var string */
    private $status;

    public function __construct($status)
    {
        $this->status = $status;
    }

    /**
     * @return string
     */
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

    public static function createdType(): self
    {
        return new self(self::$CREATED);
    }

    public static function notChangedType(): self
    {
        return new self(self::$NOT_CHANGED);
    }

    public static function isTypeAllowed(string $status): bool
    {
        return in_array($status, self::availableStatuses(), true);
    }

    /**
     * @internal
     *
     * @return string[]
     */
    public static function availableStatuses(): array
    {
        return [
            self::$CREATED,
            self::$NOT_CHANGED
        ];
    }
}
