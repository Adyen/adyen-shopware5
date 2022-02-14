<?php

declare(strict_types=1);

namespace Unit\Models\RecurringPayment;

use AdyenPayment\Models\RecurringPayment\RecurringProcessingModel;
use PHPUnit\Framework\TestCase;

class RecurringProcessingModelTest extends TestCase
{
    /** @test */
    public function it_can_construct_through_named_constructor(): void
    {
        $this->assertInstanceOf(RecurringProcessingModel::class, RecurringProcessingModel::cardOnFile());
    }

    /**
     * @dataProvider recurringProcessingModelProvider
     * @test
     */
    public function it_contains_recurring_processing_model(
        RecurringProcessingModel $recurringProcessingModel, string $expected
    ): void {
        $this->assertEquals($expected, $recurringProcessingModel->value());
    }

    public function recurringProcessingModelProvider(): \Generator
    {
        yield [RecurringProcessingModel::cardOnFile(), 'CardOnFile'];
        yield [RecurringProcessingModel::subscription(), 'Subscription'];
    }

    /** @test  */
    public function it_throws_an_invalid_argument_exception_when_recurring_processing_model_is_unknown(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid recurring processing model: "test"');

        RecurringProcessingModel::load('test');
    }

    /** @test  */
    public function it_can_check_it_is_equal_to_another_value_object(): void
    {
        $this->assertTrue(RecurringProcessingModel::cardOnFile()->equals(RecurringProcessingModel::cardOnFile()));
    }

    /** @test  */
    public function it_can_check_it_is_not_equal_to_another_value_object(): void
    {
        $this->assertFalse(RecurringProcessingModel::cardOnFile()->equals(RecurringProcessingModel::subscription()));
    }

    /** @test  */
    public function it_can_load_a_recurring_processing_model(): void
    {
        $this->assertEquals(
            RecurringProcessingModel::cardOnFile(),
            RecurringProcessingModel::load('CardOnFile')
        );
    }
}
