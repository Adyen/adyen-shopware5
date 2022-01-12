<?php

declare(strict_types=1);

namespace AdyenPayment\Tests\Unit\Certificate\Transport;

use AdyenPayment\Certificate\Transport\StreamTransportHandler;
use Phpro\HttpTools\Client\Factory\AutoDiscoveredClientFactory;
use Phpro\HttpTools\Encoding\DecoderInterface;
use Phpro\HttpTools\Encoding\EncoderInterface;
use Phpro\HttpTools\Transport\EncodedTransportFactory;
use Phpro\HttpTools\Uri\TemplatedUriBuilder;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;

class StreamTransportHandlerTest extends TestCase
{
    use ProphecyTrait;
    private StreamTransportHandler $streamTransport;

    /** @var EncoderInterface|ObjectProphecy */
    private $applePayCertificateEncoder;

    /** @var DecoderInterface|ObjectProphecy */
    private $applePayCertificateDecoder;

    protected function setUp(): void
    {
        $this->applePayCertificateEncoder = $this->prophesize(EncoderInterface::class);
        $this->applePayCertificateDecoder = $this->prophesize(DecoderInterface::class);

        $this->streamTransport = new StreamTransportHandler(
            AutoDiscoveredClientFactory::create([]),
            new TemplatedUriBuilder(),
            $this->applePayCertificateEncoder->reveal(),
            $this->applePayCertificateDecoder->reveal()
        );
    }

    /** @test */
    public function it_can_create_an_encoded_transport(): void
    {
        $encoder = $this->prophesize(EncoderInterface::class);
        $decoder = $this->prophesize(DecoderInterface::class);

        $transport = $this->streamTransport->__invoke();

        static::assertEquals(EncodedTransportFactory::sync(
            AutoDiscoveredClientFactory::create([]),
            new TemplatedUriBuilder(),
            $encoder->reveal(),
            $decoder->reveal()
        ), $transport);
    }
}
