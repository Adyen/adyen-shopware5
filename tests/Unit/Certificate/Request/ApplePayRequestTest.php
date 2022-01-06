<?php

declare(strict_types=1);

namespace AdyenPayment\Tests\Unit\Certificate\Request;

use AdyenPayment\Certificate\Request\ApplePayRequest;
use Phpro\HttpTools\Request\RequestInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;

class ApplePayRequestTest extends TestCase
{
    use ProphecyTrait;

    /** @var ApplePayRequest|ObjectProphecy */
    private $applePayRequest;

    protected function setUp(): void
    {
        $this->applePayRequest = $this->prophesize(ApplePayRequest::class);
    }

    /** @test */
    public function it_is_a_request(): void
    {
        $this->assertInstanceOf(RequestInterface::class, ApplePayRequest::create());
    }

    /** @test */
    public function it_contains_method(): void
    {
        $this->assertEquals('GET', ApplePayRequest::create()->method());
    }

    /** @test */
    public function it_contains_uri(): void
    {
        $this->assertEquals('', ApplePayRequest::create()->uri());
    }

    /** @test */
    public function it_contains_uri_parameters(): void
    {
        $this->assertEquals([], ApplePayRequest::create()->uriParameters());
    }

    /** @test */
    public function it_contains_body(): void
    {
        $this->assertEquals([], ApplePayRequest::create()->body());
    }
}
