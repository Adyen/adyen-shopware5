<?php

declare(strict_types=1);

namespace AdyenPayment\Guzzle\Middleware;

use AdyenPayment\Http\Logging\RequestFormatter;
use GuzzleHttp\Middleware;
use Psr\Http\Message\RequestInterface;
use Psr\Log\LoggerInterface;

final class RequestLogger
{
    private LoggerInterface $logger;
    private RequestFormatter $formatter;

    public function __construct(LoggerInterface $logger, RequestFormatter $formatter)
    {
        $this->logger = $logger;
        $this->formatter = $formatter;
    }

    public function __invoke(): callable
    {
        return Middleware::mapRequest(function(RequestInterface $request): RequestInterface {
            $this->logger->info($this->formatter->format($request), ['request' => $request]);

            return $request;
        });
    }
}
