<?php

declare(strict_types=1);

namespace AdyenPayment\Certificate\Transport;

use Phpro\HttpTools\Encoding\DecoderInterface;
use Phpro\HttpTools\Encoding\EncoderInterface;
use Phpro\HttpTools\Transport\EncodedTransportFactory;
use Phpro\HttpTools\Transport\TransportInterface;
use Phpro\HttpTools\Uri\UriBuilderInterface;
use Psr\Http\Client\ClientInterface;

class StreamTransportHandler
{
    private ClientInterface $client;
    private UriBuilderInterface $uriBuilder;
    private EncoderInterface $encoder;
    private DecoderInterface $decoder;

    public function __construct(
        ClientInterface $client,
        UriBuilderInterface $uriBuilder,
        EncoderInterface $encoder,
        DecoderInterface $decoder
    ) {
        $this->client = $client;
        $this->uriBuilder = $uriBuilder;
        $this->encoder = $encoder;
        $this->decoder = $decoder;
    }

    public function __invoke(): TransportInterface
    {
        return EncodedTransportFactory::sync(
            $this->client,
            $this->uriBuilder,
            $this->encoder,
            $this->decoder
        );
    }
}
