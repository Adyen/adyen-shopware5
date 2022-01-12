<?php

declare(strict_types=1);

namespace AdyenPayment\Tests\Unit\Certificate\Response;

use AdyenPayment\Certificate\Filesystem\CertificateWriterInterface;
use AdyenPayment\Certificate\Filesystem\ZipExtractorInterface;
use AdyenPayment\Certificate\Model\ApplePayCertificate;
use AdyenPayment\Certificate\Response\ApplePayResponse;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;

class ApplePayResponseTest extends TestCase
{
    use ProphecyTrait;
    private $zipExtractor;
    private $certificateWriter;

    protected function setUp(): void
    {
        $this->zipExtractor = $this->prophesize(ZipExtractorInterface::class);
        $this->certificateWriter = $this->prophesize(CertificateWriterInterface::class);

        $applePayResponse = new ApplePayResponse(
            $this->zipExtractor->reveal(),
            $this->certificateWriter->reveal()
        );
    }

    /** @test */
    public function it_creates_from_raw(): void
    {
        $applePayResponse = new ApplePayResponse(
            $this->zipExtractor->reveal(),
            $this->certificateWriter->reveal()
        );

        $applePay = ApplePayCertificate::create($certificate = 'test');
        $this->certificateWriter->__invoke(
            Argument::cetera(),
            Argument::cetera(),
            $applePay->certificate()
        )->shouldBeCalledOnce();

        $response = $applePayResponse->createFromRaw($certificate);

        self::assertEquals($applePay, $response);
    }

    /** @test */
    public function it_creates_from_fallback_zip(): void
    {
        $applePayResponse = new ApplePayResponse(
            $this->zipExtractor->reveal(),
            $this->certificateWriter->reveal()
        );

        $applePay = ApplePayCertificate::create($certificate = 'test fallback');
        $this->zipExtractor->__invoke(
            Argument::cetera(),
            Argument::cetera(),
            Argument::cetera(),
            Argument::cetera(),
        )->shouldBeCalledOnce();

        $applePayResponse->createFromFallbackZip();
    }
}
