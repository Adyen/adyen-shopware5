<?php

declare(strict_types=1);

namespace Unit\Models;

use AdyenPayment\Models\PaymentResultCodes;
use PHPUnit\Framework\TestCase;

class PaymentResultCodesTest extends TestCase
{
    /** @test */
    public function it_can_construct_through_named_constructor(): void
    {
        $this->assertInstanceOf(PaymentResultCodes::class, PaymentResultCodes::authorised());
    }

    /**
     * @dataProvider resultCodeProvider
     * @test
     */
    public function it_contains_result_code(PaymentResultCodes $resultCode, string $code): void
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
    public function it_can_check_it_is_equal_to_another_value_object(): void
    {
        $this->assertTrue(PaymentResultCodes::authorised()->equals(PaymentResultCodes::authorised()));
    }

    /** @test  */
    public function it_can_check_it_is_not_equal_to_another_value_object(): void
    {
        $this->assertFalse(PaymentResultCodes::authorised()->equals(PaymentResultCodes::cancelled()));
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
