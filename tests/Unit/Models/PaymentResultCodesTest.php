<?php

declare(strict_types=1);

namespace Unit\Models;

use AdyenPayment\Models\PaymentResultCodes;
use PHPUnit\Framework\TestCase;

class PaymentResultCodesTest extends TestCase
{
    private PaymentResultCodes $paymentResultCodes;

    protected function setUp(): void
    {
        $this->paymentResultCodes = PaymentResultCodes::authorised();
    }

    /** @test */
    public function it_contains_payment_result_codes(): void
    {
        $this->assertInstanceOf(PaymentResultCodes::class, $this->paymentResultCodes);
    }

    /** @test */
    public function it_can_compare_payment_result_codes_objects(): void
    {
        $this->assertTrue($this->paymentResultCodes->equals(PaymentResultCodes::authorised()));
        $this->assertFalse($this->paymentResultCodes->equals(PaymentResultCodes::invalid()));
    }

    /** @test */
    public function it_checks_payment_result_codes_on_immutabillity(): void
    {
        $paymentResultCodeAuthorised = PaymentResultCodes::authorised();
        $this->assertEquals($this->paymentResultCodes, $paymentResultCodeAuthorised);
        $this->assertNotSame($this->paymentResultCodes, $paymentResultCodeAuthorised);
    }

    /**
     * @dataProvider resultCodeProvider
     * @test
     */
    public function it_can_be_constructed_with_named_constructors(PaymentResultCodes $resultCode, string $code): void
    {
        $this->assertEquals($code, $resultCode->resultCode());
    }

    public function resultCodeProvider(): \Generator
    {
        yield [PaymentResultCodes::authorised(), 'Authorised'];
        yield [PaymentResultCodes::cancelled(), 'Cancelled'];
        yield [PaymentResultCodes::challengeShopper(), 'ChallengeShopper'];
        yield [PaymentResultCodes::error(), 'Error'];
        yield [PaymentResultCodes::invalid(), 'Invalid'];
        yield [PaymentResultCodes::identifyShopper(), 'IdentifyShopper'];
        yield [PaymentResultCodes::pending(), 'Pending'];
        yield [PaymentResultCodes::received(), 'Received'];
        yield [PaymentResultCodes::redirectShopper(), 'RedirectShopper'];
        yield [PaymentResultCodes::refused(), 'Refused'];
    }

    /** @test  */
    public function it_throws_an_invalid_argument_exception_when_result_code_is_unknown(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid result code: "test"');

        PaymentResultCodes::load('test');
    }

    /** @test  */
    public function it_can_load_a_result_code(): void
    {
        $this->assertEquals(
            PaymentResultCodes::authorised(),
            PaymentResultCodes::load('Authorised')
        );
    }
}
