<?php

declare(strict_types=1);

namespace AdyenPayment\Tests\Unit\Certificate\Request\Handler;

use AdyenPayment\Certificate\Filesystem\CertificateWriterInterface;
use AdyenPayment\Certificate\Filesystem\ZipExtractorInterface;
use AdyenPayment\Certificate\Request\ApplePayCertificateRequest;
use AdyenPayment\Certificate\Request\Handler\ApplePayTransportHandler;
use AdyenPayment\Certificate\Request\Handler\ApplePayTransportHandlerInterface;
use AdyenPayment\Certificate\Transport\StreamTransportHandlerInterface;
use Phpro\HttpTools\Transport\TransportInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Http\Message\StreamInterface;

class ApplePayTransportHandlerTest extends TestCase
{
    use ProphecyTrait;

    /** @var ObjectProphecy|TransportInterface */
    private $transport;

    /** @var ObjectProphecy|StreamTransportHandlerInterface */
    private $streamTransportHandler;

    /** @var CertificateWriterInterface|ObjectProphecy */
    private $certificateWriter;

    /** @var ObjectProphecy|ZipExtractorInterface */
    private $zipExtractor;
    private ApplePayTransportHandler $applePayTransportHandler;

    protected function setUp(): void
    {
        $this->streamTransportHandler = $this->prophesize(StreamTransportHandlerInterface::class);
        $this->transport = $this->prophesize(TransportInterface::class);
        $this->certificateWriter = $this->prophesize(CertificateWriterInterface::class);
        $this->zipExtractor = $this->prophesize(ZipExtractorInterface::class);

        $this->applePayTransportHandler = new ApplePayTransportHandler(
            $this->transport->reveal(),
            $this->certificateWriter->reveal(),
            $this->zipExtractor->reveal()
        );
    }

    /** @test */
    public function it_is_an_apple_pay_handler(): void
    {
        $this->assertInstanceOf(ApplePayTransportHandlerInterface::class, $this->applePayTransportHandler);
    }

    /** @test */
    public function it_uses_fallback_zip_when_stream_is_empty(): void
    {
        $request = ApplePayCertificateRequest::create();

        $streamData = $this->prophesize(StreamInterface::class);
        $this->transport->__invoke($request)->willReturn($streamData);

        $streamData->getContents()->willReturn($emptyStream = '');

        $this->zipExtractor->__invoke()->shouldBeCalledOnce();

        ($this->applePayTransportHandler)($request);
    }

    /** @test */
    public function it_uses_adyen_apple_pay_certificate_from_response(): void
    {
        $request = ApplePayCertificateRequest::create();

        $streamData = $this->prophesize(StreamInterface::class);
        $this->transport->__invoke($request)->willReturn($streamData);

        $streamData->getContents()->willReturn($certificateContent = 'test-certificate');

        $this->certificateWriter->__invoke($certificateContent)->shouldBeCalledOnce();

        ($this->applePayTransportHandler)($request);
    }
}
