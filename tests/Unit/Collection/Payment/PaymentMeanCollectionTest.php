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
    private PaymentMeanCollection $collection;

    protected function setUp(): void
    {
        $this->collection = new PaymentMeanCollection();
    }

    /** @test */
    public function it_implements_iterable(): void
    {
        self::assertInstanceOf(\IteratorAggregate::class, $this->collection);
    }

    /** @test */
    public function it_can_count(): void
    {
        self::assertInstanceOf(\Countable::class, $this->collection);
        self::assertCount(0, $this->collection);
    }

    /** @test */
    public function it_can_map_with_a_callback(): void
    {
        $filteredSource = SourceType::adyen();
        $collection = PaymentMeanCollection::createFromShopwareArray([
            ['source' => $filteredSource->getType()],
        ]);

        $result = $collection->map(static fn(PaymentMean $payment) => ['mapped']);

        self::assertEquals([['mapped']], $result);
    }

    /** @test */
    public function it_can_filter_by_source(): void
    {
        $filteredSource = SourceType::adyen();

        $collection = PaymentMeanCollection::createFromShopwareArray([
            ['id' => $expected = 123, 'source' => $filteredSource->getType()],
            ['id' => 456, 'source' => '1'],
        ]);

        $result = $collection->filterBySource($filteredSource);

        self::assertInstanceOf(PaymentMeanCollection::class, $result);
        self::assertCount(1, $result);
        self::assertEquals($expected, iterator_to_array($result)[0]->getId());
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
        $collection = PaymentMeanCollection::createFromShopwareArray([
            ['id' => 123, 'source' => $filteredSource->getType(), 'hide' => true],
            ['id' => $expected = 345, 'source' => $filteredSource->getType()],
        ]);

        $result = $collection->filterExcludeHidden();

        self::assertInstanceOf(PaymentMeanCollection::class, $result);
        self::assertCount(1, $result);
        self::assertEquals($expected, iterator_to_array($result)[0]->getId());
    }

    /** @test */
    public function it_can_fetch_umbrella_payment_if_available(): void
    {
        $collection = PaymentMeanCollection::createFromShopwareArray([
            ['source' => SourceType::adyen()->getType(), 'name' => AdyenPayment::ADYEN_STORED_PAYMENT_UMBRELLA_CODE],
            ['source' => '1'],
        ]);

        $result = $collection->fetchStoredMethodUmbrellaPaymentMean();

        self::assertInstanceOf(PaymentMean::class, $result);
        self::assertEquals(AdyenPayment::ADYEN_STORED_PAYMENT_UMBRELLA_CODE, $result->getValue('name'));
    }

    /** @test */
    public function it_will_return_null_on_fetch_umbrella_if_payment_not_available(): void
    {
        $collection = PaymentMeanCollection::createFromShopwareArray([
            ['source' => SourceType::adyen()->getType()],
            ['source' => '1'],
        ]);

        $result = $collection->fetchStoredMethodUmbrellaPaymentMean();

        self::assertNull($result);
    }

    /** @test */
    public function it_can_fetch_a_payment_by_stored_method_id(): void
    {
        $collection = PaymentMeanCollection::createFromShopwareArray([
            ['id' => 123, 'source' => SourceType::adyen()->getType()],
            ['id' => $expected = 456, 'source' => '1', 'stored_method_id' => $paymentMeanId = 'test123'],
        ]);

        $result = $collection->fetchByStoredMethodId($paymentMeanId);

        self::assertInstanceOf(PaymentMean::class, $result);
        self::assertEquals(1, $result->getSource()->getType());
        self::assertEquals($expected, $result->getId());
    }

    /** @test */
    public function it_can_fetch_a_payment_by_payment_id(): void
    {
        $filteredSource = SourceType::adyen();
        $collection = PaymentMeanCollection::createFromShopwareArray([
            ['id' => $paymentMeanId = 123, 'source' => $filteredSource->getType()],
            ['id' => '456', 'source' => '1'],
        ]);

        $result = $collection->fetchById($paymentMeanId);

        self::assertInstanceOf(PaymentMean::class, $result);
        self::assertEquals($paymentMeanId, $result->getId());
    }

    /** @test */
    public function it_returns_collection_in_shopware_array_format(): void
    {
        $filteredSource = SourceType::adyen();
        $paymentData = ['id' => '123', 'source' => $filteredSource->getType()];
        $expected = [$paymentData['id'] => $paymentData];
        $collection = PaymentMeanCollection::createFromShopwareArray([$paymentData]);

        $result = $collection->toShopwareArray();

        self::assertEquals($expected, $result);
    }
}
