<?php

declare(strict_types=1);

namespace Unit\Models\RecurringPayment;

use AdyenPayment\Models\RecurringPayment\RecurringProcessingModel;
use PHPUnit\Framework\TestCase;

class RecurringProcessingModelTest extends TestCase
{
    private RecurringProcessingModel $recurringProcessingModel;

    protected function setUp(): void
    {
        $this->recurringProcessingModel = RecurringProcessingModel::cardOnFile();
    }

    /** @test */
    public function it_contains_a_recurring_processing_model(): void
    {
        $this->assertInstanceOf(RecurringProcessingModel::class, $this->recurringProcessingModel);
    }

    /** @test */
    public function it_knows_when_it_equals_a_processing_model(): void
    {
        $this->assertTrue($this->recurringProcessingModel->equals(RecurringProcessingModel::cardOnFile()));
        $this->assertFalse($this->recurringProcessingModel->equals(RecurringProcessingModel::subscription()));
    }

    /** @test */
    public function it_knows_when_it_equals_a_recurring_processing_model(): void
    {
        $recurringProcessingModel = RecurringProcessingModel::cardOnFile();
        $this->assertEquals($this->recurringProcessingModel, $recurringProcessingModel);
        $this->assertNotSame($this->recurringProcessingModel, $recurringProcessingModel);
    }

    /** @test  */
    public function it_throws_an_invalid_argument_exception_when_recurring_processing_model_is_unknown(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid recurring processing model: "test"');

        RecurringProcessingModel::load('test');
    }

    /** @test  */
    public function it_can_load_a_recurring_processing_model(): void
    {
        $this->assertEquals(
            RecurringProcessingModel::cardOnFile(),
            RecurringProcessingModel::load('CardOnFile')
        );
    }

    /**
     * @dataProvider recurringProcessingModelProvider
     * @test
     */
    public function it_contains_recurring_processing_model(
        RecurringProcessingModel $recurringProcessingModel, string $expected
    ): void {
        $this->assertEquals($expected, $recurringProcessingModel->recurringProcessingModel());
    }

    public function recurringProcessingModelProvider(): \Generator
    {
        yield [RecurringProcessingModel::cardOnFile(), 'CardOnFile'];
        yield [RecurringProcessingModel::subscription(), 'Subscription'];
    }
}
