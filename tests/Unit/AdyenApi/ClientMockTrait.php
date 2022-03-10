<?php

declare(strict_types=1);

namespace AdyenPayment\Tests\Unit\AdyenApi;

use Adyen\Client;
use Adyen\Config;
use Adyen\Environment;
use Adyen\HttpClient\ClientInterface;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;

trait ClientMockTrait
{
    public function createClientMock(): ObjectProphecy
    {
        // we need to mock these to avoid fatal errors but the expected values are not required for these tests
        $config = $this->prophesize(Config::class);
        $config->get(Argument::any())->willReturn(Environment::TEST);
        $config->getInputType(Argument::any())->willReturn('');
        $httpClient = $this->prophesize(ClientInterface::class);
        $httpClient->requestJson(Argument::cetera())->willReturn([]);

        $client = $this->prophesize(Client::class);
        $client->getConfig()->willReturn($config->reveal());
        $client->getHttpClient()->willReturn($httpClient->reveal());
        $client->getApiCheckoutVersion()->willReturn('');
        $client->getApiRecurringVersion()->willReturn('');

        return $client;
    }
}
