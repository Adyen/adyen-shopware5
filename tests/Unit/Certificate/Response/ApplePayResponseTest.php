<?php

declare(strict_types=1);

namespace AdyenPayment\Tests\Unit\Certificate\Response;

use AdyenPayment\Certificate\Model\ApplePay;
use AdyenPayment\Certificate\Response\ApplePayResponse;
use AdyenPayment\Certificate\Service\CertificateWriterInterface;
use AdyenPayment\Certificate\Service\ZipExtractorInterface;
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
    public function it_creates_from_string(): void
    {
        $applePayResponse = new ApplePayResponse(
            $this->zipExtractor->reveal(),
            $this->certificateWriter->reveal()
        );

        $applePay = ApplePay::create($certificateString = 'test');
        $this->certificateWriter->__invoke(
            Argument::cetera(),
            Argument::cetera(),
            $applePay->certificateString()
        )->shouldBeCalledOnce()
            ->willReturn($certificateString);

        $response = $applePayResponse->createFromString($certificateString);

        self::assertEquals($applePay, $response);
    }

    /** @test */
    public function it_creates_from_fallback_zip(): void
    {
        $applePayResponse = new ApplePayResponse(
            $this->zipExtractor->reveal(),
            $this->certificateWriter->reveal()
        );

        $applePay = ApplePay::create($certificateString = 'test fallback');
        $this->zipExtractor->__invoke(
            Argument::cetera(),
            Argument::cetera(),
            Argument::cetera(),
            Argument::cetera(),
        )->shouldBeCalledOnce()
            ->willReturn($certificateString);

        $response = $applePayResponse->createFromFallbackZip();

        self::assertEquals($applePay, $response);
    }
}
