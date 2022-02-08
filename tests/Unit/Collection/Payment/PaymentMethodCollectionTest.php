<?php

declare(strict_types=1);

namespace AdyenPayment\Tests\Unit\Collection\Payment;

use AdyenPayment\AdyenPayment;
use AdyenPayment\Collection\Payment\PaymentMethodCollection;
use AdyenPayment\Models\Payment\PaymentMean;
use AdyenPayment\Models\Payment\PaymentMethod;
use PHPUnit\Framework\TestCase;
use Shopware\Bundle\StoreFrontBundle\Struct\Attribute;

final class PaymentMethodCollectionTest extends TestCase
{
    /** @test */
    public function it_implements_iterable(): void
    {
        self::assertInstanceOf(\IteratorAggregate::class, new PaymentMethodCollection());
    }

    /** @test */
    public function it_can_count(): void
    {
        $result = PaymentMethodCollection::fromAdyenMethods(['paymentMethods' => [['type' => 'someType']]]);

        self::assertInstanceOf(\Countable::class, $result);
        self::assertCount(1, $result);
    }

    /** @test */
    public function it_can_map_with_a_callback(): void
    {
        $expectedMethod = [true, false];
        $collection = PaymentMethodCollection::fromAdyenMethods(['paymentMethods' => [
            ['type' => $filteredType = 'someType'],
            ['type' => 'otherType'],
        ]]);

        $result = $collection->map(static function(PaymentMethod $payment) use ($filteredType) {
            return $filteredType === $payment->adyenType()->type();
        });

        self::assertEquals($expectedMethod, $result);
    }

    /** @test */
    public function it_can_map_to_raw(): void
    {
        $expected = [
            ['type' => 'someType'],
            ['type' => 'otherType'],
        ];
        $collection = PaymentMethodCollection::fromAdyenMethods(['paymentMethods' => $expected]);

        $result = $collection->mapToRaw();

        self::assertEquals($expected, $result);
    }

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
    public function it_can_enrich_with_import_locale(): void
    {
        $paymentMethod = PaymentMethod::fromRaw($paymentMethodData = [
            'type' => 'someType',
            'name' => $name = 'someName',
        ]);
        $expectedMethod = $paymentMethod->withCode($name);
        $paymentMethodsCollection = PaymentMethodCollection::fromAdyenMethods([
            'paymentMethods' => [$paymentMethodData],
        ]);

        $result = $paymentMethodsCollection->withImportLocale($paymentMethodsCollection);

        self::assertInstanceOf(PaymentMethodCollection::class, $result);
        self::assertEquals($expectedMethod, $result->getIterator()->current());
    }

    /** @test */
    public function it_will_return_null_on_missing_methods_for_fetch_by_payment_mean(): void
    {
        $paymentMean = PaymentMean::createFromShopwareArray([
            'id' => 1,
            'source' => 1425514,
        ]);
        $collection = new PaymentMethodCollection();

        $result = $collection->fetchByPaymentMean($paymentMean);

        self::assertNull($result);
    }

    /** @test */
    public function it_can_fetch_a_method_by_payment_mean(): void
    {
        $attribute = new Attribute();
        $attribute->set(AdyenPayment::ADYEN_CODE, 'my_adyen_code');
        $paymentMean = PaymentMean::createFromShopwareArray([
            'id' => $methodStoredId = 'test_stored_method_id',
            'source' => 1425514,
            'attribute' => $attribute,
            'stored_method_id' => $methodStoredId,
        ]);
        $testPayment = PaymentMethod::fromRaw(['type' => 'someType']);
        $expectedPayment = PaymentMethod::fromRaw(['type' => 'someType2', 'id' => $methodStoredId]);
        $collection = new PaymentMethodCollection($testPayment, $expectedPayment);

        $result = $collection->fetchByPaymentMean($paymentMean);

        self::assertSame($expectedPayment, $result);
    }

    /** @test */
    public function it_can_filter_with_a_callback(): void
    {
        $collection = PaymentMethodCollection::fromAdyenMethods(['paymentMethods' => [
            ['type' => $filteredType = 'someType'],
            ['type' => 'otherType'],
        ]]);
        $expected = PaymentMethodCollection::fromAdyenMethods(['paymentMethods' => [
            ['type' => $filteredType],
        ]]);

        $result = $collection->filter(static function(PaymentMethod $payment) use ($filteredType) {
            return $filteredType === $payment->adyenType()->type();
        });

        self::assertEquals($expected, $result);
    }
}
