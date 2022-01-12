<?php

declare(strict_types=1);

namespace AdyenPayment\Tests\Unit\Certificate\Request\Handler;

use AdyenPayment\Certificate\Filesystem\ZipExtractorInterface;
use AdyenPayment\Certificate\Model\ApplePayCertificate;
use AdyenPayment\Certificate\Request\ApplePayCertificateRequest;
use AdyenPayment\Certificate\Request\Handler\ApplePayTransportHandler;
use AdyenPayment\Certificate\Request\Handler\ApplePayTransportHandlerInterface;
use AdyenPayment\Certificate\Response\ApplePayCertificateHandlerInterface;
use AdyenPayment\Certificate\Transport\StreamTransportHandler;
use Phpro\HttpTools\Transport\TransportInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Http\Message\StreamInterface;

class ApplePayTransportHandlerTest extends TestCase
{
    use ProphecyTrait;

    /** @var ObjectProphecy|StreamTransportHandler */
    private $streamTransportFactory;

    /** @var ApplePayCertificateHandlerInterface|ObjectProphecy */
    private $applePayCertificateHandler;
    private ApplePayTransportHandler $applePayTransportHandler;

    /** @var ObjectProphecy|ZipExtractorInterface */
    private $zipExtractor;

    protected function setUp(): void
    {
        $this->streamTransportFactory = $this->prophesize(StreamTransportHandler::class);
        $this->applePayCertificateHandler = $this->prophesize(ApplePayCertificateHandlerInterface::class);
        $this->zipExtractor = $this->prophesize(ZipExtractorInterface::class);

        $this->applePayTransportHandler = new ApplePayTransportHandler(
            $this->streamTransportFactory->reveal(),
            $this->applePayCertificateHandler->reveal(),
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

        $transport = $this->prophesize(TransportInterface::class);
        $this->streamTransportFactory->__invoke()
            ->shouldBeCalledOnce()
            ->willReturn($transport->reveal());

        $streamData = $this->prophesize(StreamInterface::class);
        $transport
            ->__invoke($request)
            ->shouldBeCalled()
            ->willReturn($streamData);

        $streamData->getContents()->willReturn($emptyStream = '');

        $this->zipExtractor->__invoke()
            ->shouldBeCalledOnce()
            ->willReturn($defaultCertificate = ApplePayCertificate::create('default-content'));

        $transportHandlerResult = ($this->applePayTransportHandler)($request);
        self::assertEquals($defaultCertificate->certificate(), $transportHandlerResult->certificate());
    }

    /** @test */
    public function it_uses_adyen_apple_pay_certificate_from_response(): void
    {
        $request = ApplePayCertificateRequest::create();

        $transport = $this->prophesize(TransportInterface::class);
        $this->streamTransportFactory->__invoke()
            ->shouldBeCalledOnce()
            ->willReturn($transport->reveal());

        $streamData = $this->prophesize(StreamInterface::class);
        $transport
            ->__invoke($request)
            ->shouldBeCalled()
            ->willReturn($streamData);

        $streamData->getContents()->willReturn($certificateContent = 'test-certificate');

        $this->applePayCertificateHandler->__invoke($certificateContent)
            ->shouldBeCalledOnce()
            ->willReturn($adyenApplePayCertificate = ApplePayCertificate::create($certificateContent));

        $transportHandlerResult = ($this->applePayTransportHandler)($request);
        self::assertEquals($adyenApplePayCertificate->certificate(), $transportHandlerResult->certificate());
    }
}
