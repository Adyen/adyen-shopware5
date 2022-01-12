<?php

declare(strict_types=1);

namespace AdyenPayment\Tests\Unit\Certificate\Response;

use AdyenPayment\Certificate\Filesystem\CertificateWriterInterface;
use AdyenPayment\Certificate\Model\ApplePayCertificate;
use AdyenPayment\Certificate\Response\ApplePayCertificateHandler;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;

class ApplePayCertificateHandlerTest extends TestCase
{
    use ProphecyTrait;
    private $certificateWriter;

    protected function setUp(): void
    {
        $this->certificateWriter = $this->prophesize(CertificateWriterInterface::class);

        $applePayResponse = new ApplePayCertificateHandler(
            $this->certificateWriter->reveal()
        );
    }

    /** @test */
    public function it_creates_apple_pay_certificate_from_response(): void
    {
        $applePayResponse = new ApplePayCertificateHandler(
            $this->certificateWriter->reveal()
        );

        $applePay = ApplePayCertificate::create($certificate = 'test');

        $this->certificateWriter->__invoke(Argument::cetera())->shouldBeCalledOnce();

        $response = $applePayResponse->__invoke($certificate);

        self::assertEquals($applePay, $response);
    }
}
