<?php

declare(strict_types=1);

namespace AdyenPayment\Guzzle\Middleware;

use AdyenPayment\Http\Logging\ResponseFormatter;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Promise\Promise;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;

final class ResponseLogger
{
    private LoggerInterface $logger;
    private ResponseFormatter $formatter;

    public function __construct(LoggerInterface $logger, ResponseFormatter $formatter)
    {
        $this->logger = $logger;
        $this->formatter = $formatter;
    }

    public function __invoke(): callable
    {
        return fn(callable $handler): callable => function(
            RequestInterface $request,
            array $options
        ) use (
            $handler
        ): Promise {
            return $handler($request, $options)->then(
                function(ResponseInterface $response) use ($request): ResponseInterface {
                    $this->logger->info(
                        sprintf("Received response:\n%s", $this->formatter->format($response)),
                        ['response' => $response]
                    );

                    if ($response->getStatusCode() < 400) {
                        return $response;
                    }

                    $this->logger->error(RequestException::create($request, $response)->getMessage(), [
                        'request' => $request,
                        'response' => $response,
                    ]);

                    return $response;
                }
            );
        };
    }
}
