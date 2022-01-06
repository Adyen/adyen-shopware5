<?php

declare(strict_types=1);

namespace AdyenPayment\Tests\Unit\Certificate\Request\Handler;

use AdyenPayment\Certificate\Decoder\ApplePayCertificateDecoder;
use AdyenPayment\Certificate\Encoder\ApplePayCertificateEncoder;
use AdyenPayment\Certificate\Model\ApplePay;
use AdyenPayment\Certificate\Request\ApplePayRequest;
use AdyenPayment\Certificate\Request\Handler\ApplePayHandler;
use AdyenPayment\Certificate\Request\Handler\ApplePayHandlerInterface;
use AdyenPayment\Certificate\Transport\StreamTransportFactory;
use Phpro\HttpTools\Transport\TransportInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;

class ApplePayHandlerTest extends TestCase
{
    use ProphecyTrait;

    /** @var ObjectProphecy|StreamTransportFactory */
    private $streamTransportFactory;

    /** @var ApplePayCertificateEncoder|ObjectProphecy */
    private $applePayCertificateEncoder;

    /** @var ApplePayCertificateDecoder|ObjectProphecy */
    private $applePayCertificateDecoder;
    private ApplePayHandler $applePayHandler;

    protected function setUp(): void
    {
        $this->streamTransportFactory = $this->prophesize(StreamTransportFactory::class);
        $this->applePayCertificateEncoder = $this->prophesize(ApplePayCertificateEncoder::class);
        $this->applePayCertificateDecoder = $this->prophesize(ApplePayCertificateDecoder::class);

        $this->applePayHandler = new ApplePayHandler(
            $this->streamTransportFactory->reveal(),
            $this->applePayCertificateEncoder->reveal(),
            $this->applePayCertificateDecoder->reveal()
        );
    }

    /** @test */
    public function it_is_an_apple_pay_handler(): void
    {
        $this->assertInstanceOf(ApplePayHandlerInterface::class, $this->applePayHandler);
    }

    /** @test */
    public function it_uses_transport_to_make_request(): void
    {
        $request = ApplePayRequest::create();
        $applePay = ApplePay::create('test');

        $transport = $this->prophesize(TransportInterface::class);
        $this->streamTransportFactory->create(
            Argument::cetera(),
            Argument::cetera()
        )->shouldBeCalledOnce()
            ->willReturn($transport->reveal());

        $transport
            ->__invoke($request)
            ->willReturn($applePay);

        $response = ($this->applePayHandler)($request);

        self::assertEquals($applePay, $response);
    }
}
