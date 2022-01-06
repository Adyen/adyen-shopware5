<?php

declare(strict_types=1);

namespace AdyenPayment\Tests\Unit\Certificate\Mapper;

use AdyenPayment\Certificate\Mapper\ResponseStatusToLogLevel;
use AdyenPayment\Certificate\Mapper\ResponseStatusToLogLevelInterface;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;

class ResponseStatusToLogLevelTest extends TestCase
{
    private ResponseStatusToLogLevel $responseStatusToLogLevel;

    protected function setUp(): void
    {
        $this->responseStatusToLogLevel = new ResponseStatusToLogLevel();
    }

    /** @test */
    public function it_is_response_status_to_log_level_service(): void
    {
        static::assertInstanceOf(ResponseStatusToLogLevelInterface::class, $this->responseStatusToLogLevel);
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
