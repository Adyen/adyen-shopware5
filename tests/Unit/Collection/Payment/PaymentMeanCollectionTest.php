<?php

declare(strict_types=1);

namespace AdyenPayment\Tests\Unit\Collection\Payment;

use AdyenPayment\AdyenPayment;
use AdyenPayment\Collection\Payment\PaymentMeanCollection;
use AdyenPayment\Models\Enum\PaymentMethod\SourceType;
use AdyenPayment\Models\Payment\PaymentMean;
use PHPUnit\Framework\TestCase;

final class PaymentMeanCollectionTest extends TestCase
{
    /** @test */
    public function it_implements_iterable(): void
    {
        self::assertInstanceOf(\IteratorAggregate::class, new PaymentMeanCollection());
    }

    /** @test */
    public function it_can_count(): void
    {
        $result = PaymentMeanCollection::createFromShopwareArray([['source' => SourceType::adyen()->getType()]]);

        self::assertInstanceOf(\Countable::class, $result);
        self::assertCount(1, $result);
    }

    /** @test */
    public function it_can_map_with_a_callback(): void
    {
        $expectedMethod = [true];
        $filteredSource = SourceType::adyen();
        $collection = PaymentMeanCollection::createFromShopwareArray([
            ['source' => $filteredSource->getType()],
            ['source' => '1'],
        ]);

        $result = $collection->map(static function(PaymentMean $payment) use ($filteredSource) {
            return $payment->getSource()->equals($filteredSource);
        });

        self::assertEquals($expectedMethod, $result);
    }

    /** @test */
    public function it_can_filter_by_source(): void
    {
        $filteredSource = SourceType::adyen();
        $expected = PaymentMeanCollection::createFromShopwareArray([
            ['source' => $filteredSource->getType()],
        ]);

        $collection = PaymentMeanCollection::createFromShopwareArray([
            ['source' => $filteredSource->getType()],
            ['source' => '1'],
        ]);

        $result = $collection->filterBySource($filteredSource);

        self::assertEquals($expected, $result);
    }

    /** @test */
    public function it_can_exclude_adyen(): void
    {
        $filteredSource = SourceType::adyen();
        $expected = PaymentMeanCollection::createFromShopwareArray([
            ['source' => '1'],
        ]);

        $collection = PaymentMeanCollection::createFromShopwareArray([
            ['source' => $filteredSource->getType()],
            ['source' => '1'],
        ]);

        $result = $collection->filterExcludeAdyen();

        self::assertEquals($expected, $result);
    }

    /** @test */
    public function it_can_exclude_hidden(): void
    {
        $filteredSource = SourceType::adyen();
        $expected = PaymentMeanCollection::createFromShopwareArray([
            ['source' => $filteredSource->getType()],
            ['source' => '1'],
        ]);

        $collection = PaymentMeanCollection::createFromShopwareArray([
            ['source' => $filteredSource->getType(), 'hide' => true],
            ['source' => $filteredSource->getType()],
            ['source' => '1'],
            ['source' => '1', 'hide' => true],
        ]);

        $result = $collection->filterExcludeHidden();

        self::assertEquals($expected, $result);
    }

    /** @test */
    public function it_can_fetch_umbrella_payment_if_available(): void
    {
        $filteredSource = SourceType::adyen();
        $collection = PaymentMeanCollection::createFromShopwareArray([
            ['source' => $filteredSource->getType(), 'name' => AdyenPayment::ADYEN_STORED_PAYMENT_UMBRELLA_CODE],
            ['source' => '1'],
        ]);

        $result = $collection->fetchStoredMethodUmbrellaPaymentMean();

        self::assertInstanceOf(PaymentMean::class, $result);
        self::assertEquals(AdyenPayment::ADYEN_STORED_PAYMENT_UMBRELLA_CODE, $result->getValue('name'));
    }

    /** @test */
    public function it_will_return_null_on_fetch_umbrella_if_payment_not_available(): void
    {
        $filteredSource = SourceType::adyen();
        $collection = PaymentMeanCollection::createFromShopwareArray([
            ['source' => $filteredSource->getType()],
            ['source' => '1'],
        ]);

        $result = $collection->fetchStoredMethodUmbrellaPaymentMean();

        self::assertNull($result);
    }

    /** @test */
    public function it_can_fetch_a_payment_by_stored_method_id(): void
    {
        $filteredSource = SourceType::adyen();
        $collection = PaymentMeanCollection::createFromShopwareArray([
            ['source' => $filteredSource->getType()],
            ['source' => '1', 'stored_method_id' => $testId = 'test123'],
        ]);

        $result = $collection->fetchByStoredMethodId($testId);

        self::assertInstanceOf(PaymentMean::class, $result);
        self::assertEquals(1, $result->getSource()->getType());
    }

    /** @test */
    public function it_will_return_null_on_fetch_by_stored_method_id_if_payment_not_available(): void
    {
        $filteredSource = SourceType::adyen();
        $paymentData = ['id' => '123', 'source' => $filteredSource->getType()];
        $expected = [$paymentData['id'] => $paymentData];
        $collection = PaymentMeanCollection::createFromShopwareArray([$paymentData]);

        $result = $collection->toShopwareArray();

        self::assertEquals($expected, $result);
    }
}
