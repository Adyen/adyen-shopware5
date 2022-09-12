<?php

declare(strict_types=1);

namespace AdyenPayment\Tests\Unit\Models\Payment;

use AdyenPayment\Models\Payment\PaymentType;
use PHPUnit\Framework\TestCase;

final class PaymentTypeTest extends TestCase
{
    /** @var PaymentType */
    private $type;

    protected function setUp(): void
    {
        $this->type = PaymentType::googlePay();
    }

    /** @test */
    public function it_contains_a_type(): void
    {
        $this->assertEquals('paywithgoogle', $this->type->type());
    }

    /** @test */
    public function it_knows_it_equals_google_pay_type(): void
    {
        $this->assertTrue($this->type->equals(PaymentType::googlePay()));
    }

    /** @test */
    public function it_can_construct_type_apple_pay(): void
    {
        $type = PaymentType::applePay();
        $this->assertEquals('applepay', $type->type());
        $this->assertTrue($type->equals(PaymentType::applePay()));
    }

    /** @test */
    public function it_can_be_constructed_by_load(): void
    {
        $paymentType = PaymentType::load($type = 'any-type');
        $this->assertEquals($type, $paymentType->type());
        $this->assertTrue($paymentType->equals(PaymentType::load($type)));
    }
}
