<?php

declare(strict_types=1);

namespace AdyenPayment\Tests\Unit\Certificate\Logging;

use AdyenPayment\Certificate\Logging\HttpCodeToLogLevelProvider;
use AdyenPayment\Certificate\Logging\HttpCodeToLogLevelProviderInterface;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;

class HttpCodeToLogLevelProviderTest extends TestCase
{
    public HttpCodeToLogLevelProvider $httpCodeToLogLevel;

    protected function setUp(): void
    {
        $this->httpCodeToLogLevel = new HttpCodeToLogLevelProvider();
    }

    /** @test */
    public function it_is_http_code_to_log_level_service(): void
    {
        static::assertInstanceOf(HttpCodeToLogLevelProviderInterface::class, $this->httpCodeToLogLevel);
    }

    /** @test */
    public function it_returns_error_level_for_bad_request_response_code(): void
    {
        static::assertEquals(Logger::ERROR, ($this->httpCodeToLogLevel)(400));
    }

    /** @test */
    public function it_returns_error_level_for_not_found_response_code(): void
    {
        static::assertEquals(Logger::ERROR, ($this->httpCodeToLogLevel)(404));
    }

    /** @test */
    public function it_returns_critical_level_for_internal_error_response_code(): void
    {
        static::assertEquals(Logger::CRITICAL, ($this->httpCodeToLogLevel)(500));
    }

    /**
     * @dataProvider statusCodesProvider
     * @test
     */
    public function it_returns_info_level_by_default(int $statusCode): void
    {
        static::assertEquals(Logger::INFO, ($this->httpCodeToLogLevel)($statusCode));
    }

    public function statusCodesProvider(): \Generator
    {
        yield [200];
        yield [204];
        yield [301];
        yield [301];
        yield [666];
    }
}