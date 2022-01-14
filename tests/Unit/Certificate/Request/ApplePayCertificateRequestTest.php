<?php

declare(strict_types=1);

namespace AdyenPayment\Tests\Unit\Certificate\Request;

use AdyenPayment\Certificate\Request\ApplePayCertificateRequest;
use Phpro\HttpTools\Request\RequestInterface;
use PHPUnit\Framework\TestCase;

class ApplePayCertificateRequestTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    /** @test */
    public function it_is_a_request(): void
    {
        $this->assertInstanceOf(RequestInterface::class, ApplePayCertificateRequest::create());
    }

    /** @test */
    public function it_contains_method(): void
    {
        $this->assertEquals('GET', ApplePayCertificateRequest::create()->method());
    }

    /** @test */
    public function it_contains_uri(): void
    {
        $this->assertEquals('/.well-known/apple-developer-merchantid-domain-association', ApplePayCertificateRequest::create()->uri());
    }

    /** @test */
    public function it_contains_uri_parameters(): void
    {
        $this->assertEquals([], ApplePayCertificateRequest::create()->uriParameters());
    }

    /** @test */
    public function it_contains_body(): void
    {
        $this->assertEquals([], ApplePayCertificateRequest::create()->body());
    }
}
