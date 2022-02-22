<?php

declare(strict_types=1);

namespace AdyenPayment\Models\RecurringPayment;

final class RecurringProcessingModel
{
    private const CARD_ON_FILE = 'CardOnFile';
    private const SUBSCRIPTION = 'Subscription';
    private const UNSCHEDULED_CARD_ON_FILE = 'UnscheduledCardOnFile';
    private string $recurringProcessingModel;

    private function __construct(string $recurringProcessingModel)
    {
        if (!in_array($recurringProcessingModel, $this->availableRecurringProcessingModels(), true)) {
            throw new \InvalidArgumentException('Invalid recurring processing model: "'.$recurringProcessingModel.'"');
        }

        $this->recurringProcessingModel = $recurringProcessingModel;
    }

    public function recurringProcessingModel(): string
    {
        return $this->recurringProcessingModel;
    }

    public function equals(RecurringProcessingModel $recurringProcessingModel): bool
    {
        return $recurringProcessingModel->recurringProcessingModel() === $this->recurringProcessingModel;
    }

    public static function load(string $recurringProcessingModel): self
    {
        return new self($recurringProcessingModel);
    }

    public static function cardOnFile(): self
    {
        return new self(self::CARD_ON_FILE);
    }

    public static function subscription(): self
    {
        return new self(self::SUBSCRIPTION);
    }

    private function availableRecurringProcessingModels(): array
    {
        return [
            self::CARD_ON_FILE,
            self::SUBSCRIPTION,
            self::UNSCHEDULED_CARD_ON_FILE,
        ];
    }
}
