<?php

declare(strict_types=1);

namespace AdyenPayment\Tests\Unit\Models\Payment;

use AdyenPayment\Models\Payment\PaymentMethod;
use PHPUnit\Framework\TestCase;

final class PaymentMethodTest extends TestCase
{
    private PaymentMethod $paymentMethod;

    protected function setUp(): void
    {
        $this->paymentMethod = PaymentMethod::fromRaw([
            'type' => 'bcmc',
            'name' => 'Bancontact',
            'details' => [
                'key' => 'encryptedCardNumber',
            ],
        ]);
    }

    /** @test */
    public function it_contains_an_unique_identifier(): void
    {
        $this->assertEquals('bcmc_bancontact', $this->paymentMethod->uniqueIdentifier());
    }

    /** @test */
    public function it_contains_a_type(): void
    {
        $this->assertEquals('bcmc', $this->paymentMethod->adyenType()->type());
    }

    /** @test */
    public function it_contains_a_group(): void
    {
        $this->assertEquals('payment', $this->paymentMethod->group()->group());
    }

    /** @test */
    public function it_contains_raw_data(): void
    {
        $this->assertEquals([
            'type' => 'bcmc',
            'name' => 'Bancontact',
            'details' => [
                'key' => 'encryptedCardNumber',
            ],
        ], $this->paymentMethod->rawData());
    }

    /** @test */
    public function it_contains_a_adyen_name(): void
    {
        $this->assertEquals('Bancontact', $this->paymentMethod->name());
    }

    /** @test */
    public function it_contains_a_stored_payment_method_id(): void
    {
        $this->assertEquals('', $this->paymentMethod->getStoredPaymentMethodId());
    }

    /** @test */
    public function it_know_it_is_a_stored_payment(): void
    {
        $this->assertFalse($this->paymentMethod->isStoredPayment());
    }

    /** @test */
    public function it_contains_details(): void
    {
        $this->assertTrue($this->paymentMethod->hasDetails());
    }

    /** @test */
    public function it_can_serialize_minimal_state(): void
    {
        $this->assertTrue($this->paymentMethod->hasDetails());
    }

    /** @test */
    public function it_can_retrieve_values_with_default_fallback(): void
    {
        $this->assertNull($this->paymentMethod->getValue('non-exisiting-key'));
    }

    /** @test */
    public function it_can_retrieve_values_with_fallback(): void
    {
        $this->assertEquals('fallback-value', $this->paymentMethod->getValue('non-exisiting-key', 'fallback-value'));
    }

    /**
     * @test
     * @dataProvider valueDataProvider
     *
     * @param mixed $expected
     */
    public function it_can_retrieve_values($expected, string $key): void
    {
        $this->assertEquals($expected, $this->paymentMethod->getValue($key));
    }

    public function valueDataProvider(): iterable
    {
        yield ['bcmc', 'type'];
        yield ['Bancontact', 'name'];
        yield [['key' => 'encryptedCardNumber'], 'details'];
    }
}
