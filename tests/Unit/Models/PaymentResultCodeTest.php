<?php

declare(strict_types=1);

namespace Unit\Models;

use AdyenPayment\Models\PaymentResultCode;
use PHPUnit\Framework\TestCase;

class PaymentResultCodeTest extends TestCase
{
    private PaymentResultCode $paymentResultCode;

    protected function setUp(): void
    {
        $this->paymentResultCode = PaymentResultCode::authorised();
    }

    /** @test */
    public function it_knows_when_it_equals_payment_result_codes_objects(): void
    {
        $this->assertTrue($this->paymentResultCode->equals(PaymentResultCode::authorised()));
        $this->assertFalse($this->paymentResultCode->equals(PaymentResultCode::invalid()));
    }

    /** @test */
    public function it_is_immutable_constructed(): void
    {
        $paymentResultCodeAuthorised = PaymentResultCode::authorised();
        $this->assertEquals($this->paymentResultCode, $paymentResultCodeAuthorised);
        $this->assertNotSame($this->paymentResultCode, $paymentResultCodeAuthorised);
    }

    /** @test  */
    public function it_throws_an_invalid_argument_exception_when_result_code_is_unknown(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid result code: "test"');

        PaymentResultCode::load('test');
    }

    /** @test  */
    public function it_can_load_a_result_code(): void
    {
        $this->assertEquals(
            PaymentResultCode::authorised(),
            PaymentResultCode::load('Authorised')
        );
    }

    /** @test */
    public function it_knows_when_a_result_code_exists(): void
    {
        $result = PaymentResultCode::exists(PaymentResultCode::cancelled()->resultCode());

        $this->assertTrue($result);
    }

    /** @test */
    public function it_knows_when_a_result_code_doesnt_exists(): void
    {
        $result = PaymentResultCode::exists('invalid-code-test');

        $this->assertFalse($result);
    }

    /**
     * @dataProvider resultCodeProvider
     * @test
     */
    public function it_can_be_constructed_with_named_constructors(PaymentResultCode $resultCode, string $code): void
    {
        $this->assertEquals($code, $resultCode->resultCode());
    }

    public function resultCodeProvider(): \Generator
    {
        yield [PaymentResultCode::authorised(), 'Authorised'];
        yield [PaymentResultCode::cancelled(), 'Cancelled'];
        yield [PaymentResultCode::challengeShopper(), 'ChallengeShopper'];
        yield [PaymentResultCode::error(), 'Error'];
        yield [PaymentResultCode::invalid(), 'Invalid'];
        yield [PaymentResultCode::identifyShopper(), 'IdentifyShopper'];
        yield [PaymentResultCode::pending(), 'Pending'];
        yield [PaymentResultCode::received(), 'Received'];
        yield [PaymentResultCode::redirectShopper(), 'RedirectShopper'];
        yield [PaymentResultCode::refused(), 'Refused'];
    }
}
