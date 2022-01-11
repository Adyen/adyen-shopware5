<?php

declare(strict_types=1);

use AdyenPayment\Certificate\Encoder\PlainTextEncoder;
use GuzzleHttp\Psr7\Request;
use Phpro\HttpTools\Encoding\EncoderInterface;
use PHPUnit\Framework\TestCase;

class PlainTextEncoderTest extends TestCase
{
    private PlainTextEncoder $applePayCertificateEncoder;

    protected function setUp(): void
    {
        $this->applePayCertificateEncoder = new PlainTextEncoder();
    }

    /** @test */
    public function it_is_an_encoder_interface(): void
    {
        $this->assertInstanceOf(EncoderInterface::class, $this->applePayCertificateEncoder);
    }

    /** @test */
    public function it_returns_the_request(): void
    {
        $request = new Request('GET', '/some-uri');

        $encodedRequest = ($this->applePayCertificateEncoder)($request, ['foo' => 'bar']);
        static::assertEquals(['Content-Type' => ['text/plain']], $encodedRequest->getHeaders());
    }
}
