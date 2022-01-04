<?php

declare(strict_types=1);

namespace AdyenPayment\Tests\Unit\Collection\Payment;

use AdyenPayment\Collection\Payment\PaymentMethodCollection;
use AdyenPayment\Models\Payment\PaymentMethod;
use PHPUnit\Framework\TestCase;

final class PaymentMethodCollectionTest extends TestCase
{
    /** @test */
    public function it_can_map_adyen_payment_methods(): void
    {
        $expectedMethod = PaymentMethod::fromRaw($paymentMethodData = ['type' => 'someType']);
        $result = PaymentMethodCollection::fromAdyenMethods(['paymentMethods' => [$paymentMethodData]]);

        self::assertInstanceOf(PaymentMethodCollection::class, $result);
        self::assertEquals($expectedMethod, $result->getIterator()->current());
    }

    /** @test */
    public function it_can_map_adyen_stored_payment_methods(): void
    {
        $expectedMethod = PaymentMethod::fromRaw($storedPaymentMethodData = ['type' => 'someType', 'id' => '1234']);
        $result = PaymentMethodCollection::fromAdyenMethods(['storedPaymentMethods' => [$storedPaymentMethodData]]);

        self::assertInstanceOf(PaymentMethodCollection::class, $result);
        self::assertEquals($expectedMethod, $result->getIterator()->current());
    }

    /** @test */
    public function it_can_map_adyen_stored_and_non_stored_payment_methods(): void
    {
        $result = PaymentMethodCollection::fromAdyenMethods([
            'storedPaymentMethods' => [['id' => '1234']],
            'paymentMethods' => [['type' => 'someType']],
        ]);

        self::assertInstanceOf(PaymentMethodCollection::class, $result);
        self::assertEquals(2, $result->count());
    }
}
