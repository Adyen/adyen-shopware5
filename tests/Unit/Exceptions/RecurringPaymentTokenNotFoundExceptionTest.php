<?php

declare(strict_types=1);

namespace AdyenPayment\Tests\Unit\Applepay\Exceptions;

use AdyenPayment\Exceptions\RecurringPaymentTokenNotFoundException;
use AdyenPayment\Models\PaymentResultCode;
use PHPUnit\Framework\TestCase;

final class RecurringPaymentTokenNotFoundExceptionTest extends TestCase
{
    /** @test */
    public function it_can_return_an_exception_with_customer_id_and_order_number(): void
    {
        $exception = RecurringPaymentTokenNotFoundException::withCustomerIdAndOrderNumber(
            $customerId = 'customer-id',
            $orderNumber = 'order-number'
        );

        self::assertInstanceOf(\RuntimeException::class, $exception);
        self::assertInstanceOf(RecurringPaymentTokenNotFoundException::class, $exception);
        self::assertEquals(
            sprintf(
                'Recurring payment token not found with customer id: %s, order number: %s',
                $customerId,
                $orderNumber
            ),
            $exception->getMessage()
        );
    }

    /** @test */
    public function it_can_return_an_exception_with_psp_reference(): void
    {
        $exception = RecurringPaymentTokenNotFoundException::withPendingResultCodeAndPspReference(
            $pspReference = 'psp-reference'
        );

        self::assertInstanceOf(\RuntimeException::class, $exception);
        self::assertInstanceOf(RecurringPaymentTokenNotFoundException::class, $exception);
        self::assertEquals(
            sprintf(
                'Recurring payment token not found with result code: %s, psp reference: %s',
                PaymentResultCode::pending()->resultCode(),
                $pspReference
            ),
            $exception->getMessage()
        );
    }
}
