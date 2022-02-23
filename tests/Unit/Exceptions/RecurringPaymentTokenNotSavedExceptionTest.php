<?php

declare(strict_types=1);

namespace AdyenPayment\Tests\Unit\Applepay\Exceptions;

use AdyenPayment\Exceptions\RecurringPaymentTokenNotSavedException;
use AdyenPayment\Models\TokenIdentifier;
use PHPUnit\Framework\TestCase;

final class RecurringPaymentTokenNotSavedExceptionTest extends TestCase
{
    /** @test */
    public function it_can_return_an_exception_with_customer_id_and_order_number(): void
    {
        $tokenIdentifier = TokenIdentifier::generate();

        $exception = RecurringPaymentTokenNotSavedException::withId($tokenIdentifier);

        self::assertInstanceOf(\RuntimeException::class, $exception);
        self::assertInstanceOf(RecurringPaymentTokenNotSavedException::class, $exception);
        self::assertEquals(
            'Recurring payment token not saved with id:'.$tokenIdentifier->identifier(),
            $exception->getMessage()
        );
    }
}
