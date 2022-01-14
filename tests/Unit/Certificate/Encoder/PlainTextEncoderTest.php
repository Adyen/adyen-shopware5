<?php

declare(strict_types=1);

use AdyenPayment\Certificate\Encoder\PlainTextEncoder;
use GuzzleHttp\Psr7\Request;
use Phpro\HttpTools\Encoding\EncoderInterface;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;

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
    public function it_sets_the_content_header(): void
    {
        $request = new Request('GET', '/some-uri');

        $encodedRequest = ($this->applePayCertificateEncoder)($request, ['foo' => 'bar']);
        $this->assertEquals(['Content-Type' => ['text/plain']], $encodedRequest->getHeaders());
        $this->assertInstanceOf(RequestInterface::class, $encodedRequest);
        $this->assertNotEquals($request, $encodedRequest);
    }
}
