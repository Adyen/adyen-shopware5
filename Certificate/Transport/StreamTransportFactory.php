<?php

declare(strict_types=1);

namespace AdyenPayment\Certificate\Transport;

use Phpro\HttpTools\Encoding\DecoderInterface;
use Phpro\HttpTools\Encoding\EncoderInterface;
use Phpro\HttpTools\Transport\EncodedTransportFactory;
use Phpro\HttpTools\Transport\TransportInterface;
use Phpro\HttpTools\Uri\UriBuilderInterface;
use Psr\Http\Client\ClientInterface;

class StreamTransportFactory
{
    private ClientInterface $client;
    private UriBuilderInterface $uriBuilder;

    public function __construct(
        ClientInterface $client,
        UriBuilderInterface $uriBuilder
    ) {
        $this->client = $client;
        $this->uriBuilder = $uriBuilder;
    }

    public function create(EncoderInterface $encoder, DecoderInterface $decoder): TransportInterface
    {
        return EncodedTransportFactory::sync(
            $this->client,
            $this->uriBuilder,
            $encoder,
            $decoder
        );
    }
}
