<?php

declare(strict_types=1);

namespace AdyenPayment\Certificate\Middleware;

use AdyenPayment\Certificate\Mapper\ResponseStatusToLogLevelInterface;
use GuzzleHttp\Promise\PromiseInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;

final class BodyLoggingMiddleware implements MiddlewareInterface
{
    private ResponseStatusToLogLevelInterface $responseStatusToLogLevel;
    private LoggerInterface $logger;

    public function __construct(
        ResponseStatusToLogLevelInterface $responseStatusToLogLevel,
        LoggerInterface $logger
    ) {
        $this->responseStatusToLogLevel = $responseStatusToLogLevel;
        $this->logger = $logger;
    }

    public function __invoke(callable $nextHandler): callable
    {
        /** @psalm-suppress MixedInferredReturnType */
        return function(RequestInterface $request, array $options) use ($nextHandler): PromiseInterface {
            $this->logRequestBody($request);

            /** @psalm-suppress  MixedReturnStatement,MixedMethodCall */
            return $nextHandler($request, $options)->then(
                $this->logResponseBody()
            );
        };
    }

    private function logRequestBody(RequestInterface $request): void
    {
        $request->getBody()->rewind();
        $this->logger->debug(
            'Sending request to Adyen apple pay certificate domain with body',
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
                'Receiving response from Adyen apple pay certificate domain with body',
                [
                    'body' => $responseBody,
                ]
            );

            return $response;
        };
    }
}
