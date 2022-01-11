<?php

declare(strict_types=1);

namespace AdyenPayment\Certificate\Middleware;

use AdyenPayment\Certificate\Logging\HttpCodeToLogLevelProviderInterface;
use GuzzleHttp\Promise\PromiseInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;

final class HeaderLoggingMiddleware implements MiddlewareInterface
{
    private HttpCodeToLogLevelProviderInterface $httpCodeToLogLevelProvider;
    private LoggerInterface $logger;

    public function __construct(
        HttpCodeToLogLevelProviderInterface $httpCodeToLogLevelProvider,
        LoggerInterface $logger
    ) {
        $this->httpCodeToLogLevelProvider = $httpCodeToLogLevelProvider;
        $this->logger = $logger;
    }

    public function __invoke(callable $nextHandler): callable
    {
        /** @psalm-suppress MixedInferredReturnType */
        return function(RequestInterface $request, array $options) use ($nextHandler): PromiseInterface {
            $this->logRequestHeaders($request);

            /** @psalm-suppress  MixedReturnStatement,MixedMethodCall */
            return $nextHandler($request, $options)->then(
                $this->logResponseHeaders()
            );
        };
    }

    private function logRequestHeaders(RequestInterface $request): void
    {
        $this->logger->info(
            'Sending request to Adyen apple pay certificate domain with headers',
            [
                'headers' => $request->getHeaders(),
                'uri' => $request->getUri()->getPath(),
                'method' => $request->getMethod(),
            ]
        );
    }

    private function logResponseHeaders(): callable
    {
        return function(ResponseInterface $response): ResponseInterface {
            $responseStatusCode = $response->getStatusCode();

            $logLevel = ($this->httpCodeToLogLevelProvider)($responseStatusCode);

            $this->logger->log(
                $logLevel,
                'Receiving response from Adyen apple pay certificate domain with headers',
                [
                    'headers' => $response->getHeaders(),
                    'statusCode' => $responseStatusCode,
                ]
            );

            return $response;
        };
    }
}
