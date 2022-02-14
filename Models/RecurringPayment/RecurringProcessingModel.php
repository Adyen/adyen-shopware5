<?php

declare(strict_types=1);

namespace AdyenPayment\Models\RecurringPayment;

final class RecurringProcessingModel
{
    private const CARDONFILE = 'CardOnFile';
    private const SUBSCRIPTION = 'Subscription';
    private string $value;

    private function __construct(string $recurringProcessingModel)
    {
        if (!in_array($recurringProcessingModel, $this->availableRecurringProcessingModels(), true)) {
            throw new \InvalidArgumentException('Invalid recurring processing model: "'.$recurringProcessingModel.'"');
        }

        $this->value = $recurringProcessingModel;
    }

    public function value(): string
    {
        return $this->value;
    }

    public function equals(RecurringProcessingModel $recurringProcessingModel): bool
    {
        return $recurringProcessingModel->value() === $this->value;
    }

    public static function load(string $recurringProcessingModel): self
    {
        return new self($recurringProcessingModel);
    }

    private function availableRecurringProcessingModels(): array
    {
        return [
            self::CARDONFILE,
            self::SUBSCRIPTION,
        ];
    }

    public static function cardOnFile(): self
    {
        return new self(self::CARDONFILE);
    }

    public static function subscription(): self
    {
        return new self(self::SUBSCRIPTION);
    }
}
