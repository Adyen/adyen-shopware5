<?php

declare(strict_types=1);

namespace AdyenPayment\Tests\Unit\Models\Payment;

use AdyenPayment\Models\Enum\PaymentMethod\SourceType;
use AdyenPayment\Models\Payment\PaymentMean;
use AdyenPayment\Models\Payment\PaymentType;
use PHPUnit\Framework\TestCase;
use Shopware\Bundle\StoreFrontBundle\Struct\Attribute;

final class PaymentMeanTest extends TestCase
{
    /** @var PaymentMean */
    private $paymentMean;

    protected function setUp(): void
    {
        $this->paymentMean = PaymentMean::createFromShopwareArray([
            'id' => '15',
            'source' => '1425514',
            'attribute' => new Attribute([
                'adyen_type' => 'adyen-type',
            ]),
            'enriched' => true,
            'adyenType' => 'adyen-type',
            'hide' => true,
        ]);
    }

    /** @test */
    public function it_contains_an_id(): void
    {
        $this->assertIsInt($this->paymentMean->getId());
        $this->assertEquals(15, $this->paymentMean->getId());
    }

    /** @test */
    public function it_contains_a_source(): void
    {
        $this->assertInstanceOf(SourceType::class, $this->paymentMean->getSource());
        $this->assertEquals(SourceType::adyen(), $this->paymentMean->getSource());
    }

    /** @test */
    public function it_knows_it_is_hidden(): void
    {
        $this->assertTrue($this->paymentMean->isHidden());
    }

    /** @test */
    public function it_contains_raw_data(): void
    {
        $this->assertIsArray($this->paymentMean->getRaw());
        $this->assertEquals([
            'id' => '15',
            'source' => '1425514',
            'attribute' => new Attribute([
                'adyen_type' => 'adyen-type',
            ]),
            'enriched' => true,
            'adyenType' => 'adyen-type',
            'hide' => true,
        ], $this->paymentMean->getRaw());
    }

    /** @test */
    public function it_knows_when_enriched(): void
    {
        $this->assertTrue($this->paymentMean->isEnriched());
    }

    /** @test */
    public function it_contains_adyen_type(): void
    {
        $this->assertTrue($this->paymentMean->adyenType()->equals(PaymentType::load('adyen-type')));
    }

    /** @test */
    public function it_can_retrieve_a_value(): void
    {
        $this->assertIsString($this->paymentMean->getValue('id'));
        $this->assertEquals('15', $this->paymentMean->getValue('id'));
    }

    /** @test */
    public function it_can_retrieve_a_value_with_default_fallback(): void
    {
        $this->assertNull($this->paymentMean->getValue('non-existent'));
    }

    /** @test */
    public function it_can_retrieve_a_value_with_fallback(): void
    {
        $this->assertEquals('fallback', $this->paymentMean->getValue('non-existent', 'fallback'));
    }

    /** @test */
    public function it_can_retrieve_an_attribute(): void
    {
        $this->assertEquals(new Attribute([
            'adyen_type' => 'adyen-type',
        ]), $this->paymentMean->getAttribute());
    }

    /** @test */
    public function it_can_retrieve_attribute_adyen_type(): void
    {
        $this->assertEquals('adyen-type', $this->paymentMean->getAdyenCode());
    }

    /** @test */
    public function it_can_retrieve_default_attribute_adyen_type(): void
    {
        $paymentMean = PaymentMean::createFromShopwareArray(['source' => null]);
        $this->assertEquals('', $paymentMean->getAdyenCode());
    }

    /** @test */
    public function it_can_retrieve_default_attribute_adyen_stored_method_id(): void
    {
        $paymentMean = PaymentMean::createFromShopwareArray(['source' => null]);
        $this->assertEquals('', $paymentMean->getAdyenStoredMethodId());
    }
}
