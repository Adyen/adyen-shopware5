<?php

declare(strict_types=1);

namespace AdyenPayment\Tests\Unit\Certificate\Mapper;

use AdyenPayment\Certificate\Logging\ResponseStatusToLogLevelProvider;
use AdyenPayment\Certificate\Logging\ResponseStatusToLogLevelProviderInterface;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;

class ResponseStatusToLogLevelProviderTest extends TestCase
{
    private ResponseStatusToLogLevelProvider $responseStatusToLogLevel;

    protected function setUp(): void
    {
        $this->responseStatusToLogLevel = new ResponseStatusToLogLevelProvider();
    }

    /** @test */
    public function it_is_response_status_to_log_level_service(): void
    {
        static::assertInstanceOf(ResponseStatusToLogLevelProviderInterface::class, $this->responseStatusToLogLevel);
    }

    /** @test */
    public function it_returns_error_level_on_empty_body(): void
    {
        static::assertEquals(Logger::ERROR, ($this->responseStatusToLogLevel)(''));
    }

    /** @test */
    public function it_returns_debug_level_by_default(): void
    {
        static::assertEquals(Logger::DEBUG, ($this->responseStatusToLogLevel)('test'));
    }
}
