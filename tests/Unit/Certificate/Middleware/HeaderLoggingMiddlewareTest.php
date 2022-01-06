<?php

declare(strict_types=1);

namespace AdyenPayment\Tests\Unit\Certificate\Middleware;

use AdyenPayment\Certificate\Mapper\HttpCodeToLogLevelInterface;
use AdyenPayment\Certificate\Middleware\HeaderLoggingMiddleware;
use AdyenPayment\Certificate\Middleware\MiddlewareInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Log\LoggerInterface;

class HeaderLoggingMiddlewareTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var HttpCodeToLogLevelInterface|ObjectProphecy
     */
    private $httpCodeToLogLevel;

    /**
     * @var LoggerInterface|ObjectProphecy
     */
    private $logger;
    private HeaderLoggingMiddleware $headerLoggingMiddleware;

    protected function setUp(): void
    {
        $this->httpCodeToLogLevel = $this->prophesize(HttpCodeToLogLevelInterface::class);
        $this->logger = $this->prophesize(LoggerInterface::class);

        $this->headerLoggingMiddleware = new HeaderLoggingMiddleware(
            $this->httpCodeToLogLevel->reveal(),
            $this->logger->reveal()
        );
    }

    /** @test */
    public function it_is_a_client_middleware(): void
    {
        static::assertInstanceOf(MiddlewareInterface::class, $this->headerLoggingMiddleware);
    }

    /** @test */
    public function it_logs_request_and_response_headers(): void
    {
        $responseStatusCode = 333;

        $responseHeaders = [
            'Foo' => 'Bar',
            'Lorem' => 'Ipsum',
        ];

        $mock = new MockHandler([
            new Response($responseStatusCode, $responseHeaders),
        ]);

        $stack = HandlerStack::create($mock);
        $stack->unshift($this->headerLoggingMiddleware);

        $requestHost = 'some-api.be';
        $client = new Client(array_merge(['base_uri' => 'https://'.$requestHost], ['handler' => $stack]));

        $logLevel = 666;
        $this->httpCodeToLogLevel->__invoke($responseStatusCode)->willReturn(
            $logLevel
        );

        $requestHeaders = [
            'Hello' => 'World',
            'Azery' => 'Qwerty',
        ];
        $requestUri = '/some-uri/we-use-this-for-testing';
        $requestMethod = 'PATCH';

        $this->logger->info(
            'Sending request to Adyen apple pay certificate domain with headers',
            [
                'headers' => array_merge(
                    ['User-Agent' => ['GuzzleHttp/7']],
                    ['Host' => [$requestHost]],
                    array_map(static fn($value) => [$value], $requestHeaders)
                ),
                'uri' => $requestUri,
                'method' => $requestMethod,
            ]
        )->shouldBeCalled();

        $this->logger->log(
            $logLevel,
            'Receiving response from Adyen apple pay certificate domain with headers',
            [
                'headers' => array_map(static fn($value) => [$value], $responseHeaders),
                'statusCode' => $responseStatusCode,
            ]
        )->shouldBeCalled();

        $client->send(
            new Request(
                $requestMethod,
                $requestUri,
                $requestHeaders
            )
        );
    }
}
