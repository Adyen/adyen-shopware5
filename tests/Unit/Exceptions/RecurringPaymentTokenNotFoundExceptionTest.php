<?php

declare(strict_types=1);

namespace AdyenPayment\Tests\Unit\Applepay\Exceptions;

use AdyenPayment\Exceptions\RecurringPaymentTokenNotFoundException;
use AdyenPayment\Models\PaymentResultCode;
use PHPUnit\Framework\TestCase;

final class RecurringPaymentTokenNotFoundExceptionTest extends TestCase
{
    /** @var RecurringPaymentTokenNotFoundException */
    private $exception;

    protected function setUp(): void
    {
        $this->exception = new RecurringPaymentTokenNotFoundException();
    }

    /** @test */
    public function is_a_runtime_exception(): void
    {
        self::assertInstanceOf(\RuntimeException::class, $this->exception);
    }

    /** @test */
    public function it_can_be_constructed_with_customer_id_and_order_number(): void
    {
        $exception = RecurringPaymentTokenNotFoundException::withCustomerIdAndOrderNumber(
            $customerId = 'customer-id',
            $orderNumber = 'order-number'
        );

        self::assertInstanceOf(RecurringPaymentTokenNotFoundException::class, $exception);
        self::assertEquals(
            'Recurring payment token not found with customer id: "'.$customerId.'", order number: "'.$orderNumber.'"',
            $exception->getMessage()
        );
    }

    /** @test */
    public function it_can_be_constructed_with_psp_reference(): void
    {
        $exception = RecurringPaymentTokenNotFoundException::withPendingResultCodeAndPspReference(
            $pspReference = 'psp-reference'
        );

        self::assertInstanceOf(RecurringPaymentTokenNotFoundException::class, $exception);
        self::assertEquals(
            'Recurring payment token not found with result code: "'.PaymentResultCode::pending()->resultCode()
                .'", psp reference: "'.$pspReference.'"',
            $exception->getMessage()
        );
    }
}
