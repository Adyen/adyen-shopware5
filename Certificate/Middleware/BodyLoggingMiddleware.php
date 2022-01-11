<?php

declare(strict_types=1);

namespace AdyenPayment\Certificate\Middleware;

use AdyenPayment\Certificate\Logging\ResponseStatusToLogLevelProviderInterface;
use GuzzleHttp\Promise\PromiseInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;

final class BodyLoggingMiddleware implements MiddlewareInterface
{
    private ResponseStatusToLogLevelProviderInterface $responseStatusToLogLevel;
    private LoggerInterface $logger;

    public function __construct(
        ResponseStatusToLogLevelProviderInterface $responseStatusToLogLevel,
        LoggerInterface $logger
    ) {
        $this->responseStatusToLogLevel = $responseStatusToLogLevel;
        $this->logger = $logger;
    }

    public function __invoke(callable $nextHandler): callable
    {
        return function(RequestInterface $request, array $options) use ($nextHandler): PromiseInterface {
            $this->logRequestBody($request);

            return $nextHandler($request, $options)->then(
                $this->logResponseBody()
            );
        };
    }

    private function logRequestBody(RequestInterface $request): void
    {
        $request->getBody()->rewind();
        $this->logger->debug(
            'Request to Adyen - apple pay certificate',
            [
                'body' => $request->getBody()->getContents(),
            ]
        );
    }

    private function logResponseBody(): callable
    {
        return function(ResponseInterface $response): ResponseInterface {
            $responseBody = $response->getBody()->getContents();
            $response->getBody()->rewind();

            $logLevel = ($this->responseStatusToLogLevel)($responseBody);

            $this->logger->log(
                $logLevel,
                'Response from Adyen - apple pay certificate',
                [
                    'body' => mb_substr($responseBody, 0, 5),
                ]
            );

            return $response;
        };
    }
}
