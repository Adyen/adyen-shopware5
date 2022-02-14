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
        yield [PaymentResultCodes::identifyShopper(), 'IdentifyShopper'];
        yield [PaymentResultCodes::pending(), 'Pending'];
        yield [PaymentResultCodes::received(), 'Received'];
        yield [PaymentResultCodes::redirectShopper(), 'RedirectShopper'];
        yield [PaymentResultCodes::refused(), 'Refused'];
    }
}
