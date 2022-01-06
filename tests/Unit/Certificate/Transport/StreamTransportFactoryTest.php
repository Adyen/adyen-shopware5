<?php

declare(strict_types=1);

namespace AdyenPayment\Tests\Unit\Certificate\Transport;

use AdyenPayment\Certificate\Transport\StreamTransportFactory;
use Phpro\HttpTools\Client\Factory\AutoDiscoveredClientFactory;
use Phpro\HttpTools\Encoding\DecoderInterface;
use Phpro\HttpTools\Encoding\EncoderInterface;
use Phpro\HttpTools\Transport\EncodedTransportFactory;
use Phpro\HttpTools\Uri\TemplatedUriBuilder;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;

class StreamTransportFactoryTest extends TestCase
{
    use ProphecyTrait;
    private StreamTransportFactory $streamTransport;

    protected function setUp(): void
    {
        $this->streamTransport = new StreamTransportFactory(
            AutoDiscoveredClientFactory::create([]),
            new TemplatedUriBuilder()
        );
    }

    /** @test */
    public function it_can_create_an_encoded_transport(): void
    {
        $encoder = $this->prophesize(EncoderInterface::class);
        $decoder = $this->prophesize(DecoderInterface::class);

        $transport = $this->streamTransport->create(
            $encoder->reveal(),
            $decoder->reveal()
        );

        static::assertEquals(EncodedTransportFactory::sync(
            AutoDiscoveredClientFactory::create([]),
            new TemplatedUriBuilder(),
            $encoder->reveal(),
            $decoder->reveal()
        ), $transport);
    }
}
