<?php

declare(strict_types=1);

namespace AdyenPayment\Tests\Unit\Certificate\Model;

use AdyenPayment\Certificate\Model\ApplePayCertificate;
use PHPUnit\Framework\TestCase;

class ApplePayCertificateTest extends TestCase
{
    private ApplePayCertificate $applePay;

    protected function setUp(): void
    {
        $this->applePay = ApplePayCertificate::create('certificate string');
    }

    /** @test */
    public function it_contains_a_certificate_string(): void
    {
        $this->assertEquals('certificate string', $this->applePay->certificate());
    }

    /** @test */
    public function it_can_be_constructed_by_load(): void
    {
        $applePay = ApplePayCertificate::create($certificateString = 'test');
        $this->assertEquals($certificateString, $applePay->certificate());
    }
}
