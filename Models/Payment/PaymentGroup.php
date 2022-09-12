<?php

declare(strict_types=1);

namespace AdyenPayment\Models\Payment;

final class PaymentGroup
{
    private const DEFAULT = 'payment';
    private const STORED = 'stored';

    /** @var string */
    private $group;

    private function __construct(string $group)
    {
        if (!in_array($group, $this->availableGroups(), true)) {
            throw new \InvalidArgumentException('Invalid Payment method group: "'.$group.'"');
        }

        $this->group = $group;
    }

    public static function default(): self
    {
        return new self(self::DEFAULT);
    }

    public static function stored(): self
    {
        return new self(self::STORED);
    }

    public function group(): string
    {
        return $this->group;
    }

    public function equals(PaymentGroup $group): bool
    {
        return $this->group === $group->group();
    }

    /**
     * @return string[]
     */
    private function availableGroups(): array
    {
        return [
            self::DEFAULT,
            self::STORED,
        ];
    }
}
