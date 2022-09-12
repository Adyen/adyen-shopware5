<?php

declare(strict_types=1);

namespace AdyenPayment\Tests\Unit\Applepay\Exceptions;

use AdyenPayment\Exceptions\RecurringPaymentTokenNotSavedException;
use AdyenPayment\Models\TokenIdentifier;
use PHPUnit\Framework\TestCase;

final class RecurringPaymentTokenNotSavedExceptionTest extends TestCase
{
    /** @var RecurringPaymentTokenNotSavedException */
    private $exception;

    protected function setUp(): void
    {
        $this->exception = new RecurringPaymentTokenNotSavedException();
    }

    /** @test */
    public function is_a_runtime_exception(): void
    {
        self::assertInstanceOf(\RuntimeException::class, $this->exception);
    }

    /** @test */
    public function it_can_be_constructed_with_token_identifier(): void
    {
        $tokenIdentifier = TokenIdentifier::generate();

        $exception = RecurringPaymentTokenNotSavedException::withId($tokenIdentifier);

        self::assertInstanceOf(RecurringPaymentTokenNotSavedException::class, $exception);
        self::assertEquals(
            'Recurring payment token not saved with id: "'.$tokenIdentifier->identifier().'"',
            $exception->getMessage()
        );
    }
}
