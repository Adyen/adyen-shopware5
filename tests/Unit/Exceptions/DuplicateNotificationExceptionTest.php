<?php

declare(strict_types=1);

namespace AdyenPayment\Tests\Unit\Exceptions;

use AdyenPayment\Exceptions\DuplicateNotificationException;
use AdyenPayment\Models\Notification;
use PHPUnit\Framework\TestCase;

final class DuplicateNotificationExceptionTest extends TestCase
{
    /** @var DuplicateNotificationException */
    private $exception;

    protected function setUp(): void
    {
        $this->exception = new DuplicateNotificationException();
    }

    /** @test */
    public function is_a_runtime_exception(): void
    {
        self::assertInstanceOf(\RuntimeException::class, $this->exception);
    }

    /** @test */
    public function it_can_be_constructed_with_a_notification(): void
    {
        $notification = new Notification();
        $notification
            ->setId($id = 1)
            ->setOrderId($orderId = 2)
            ->setPspReference($pspReference = 'PSP_REF_1')
            ->setStatus('received')
            ->setPaymentMethod('mc')
            ->setEventCode('AUTHORISATION')
            ->setSuccess(true)
            ->setMerchantAccountCode('Adyen-test')
            ->setAmountValue(4598.0000)
            ->setAmountCurrency('EUR');

        $exception = DuplicateNotificationException::withNotification($notification);

        self::assertInstanceOf(DuplicateNotificationException::class, $exception);
        self::assertEquals('Duplicate notification is not handled. Notification with id: "1", orderId: "2", pspReference: "PSP_REF_1", status: "received", paymentMethod: "mc", eventCode: "AUTHORISATION", success: "1", merchantAccountCode: "Adyen-test", amountValue: "4598", amountCurrency: "EUR"',
            $exception->getMessage()
        );
    }
}
