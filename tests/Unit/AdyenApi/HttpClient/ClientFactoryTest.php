<?php

declare(strict_types=1);

namespace AdyenPayment\Tests\Unit\AdyenApi\HttpClient;

use Adyen\Client;
use Adyen\Environment;
use AdyenPayment\AdyenApi\HttpClient\ClientFactory;
use AdyenPayment\Components\ConfigurationInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Log\LoggerInterface;
use Shopware\Models\Shop\Shop;

class ClientFactoryTest extends TestCase
{
    use ProphecyTrait;

    /** @var ClientFactory */
    private $clientFactory;

    /** @var ConfigurationInterface|ObjectProphecy */
    private $configuration;

    /** @var LoggerInterface|ObjectProphecy */
    private $logger;

    protected function setUp(): void
    {
        $this->configuration = $this->prophesize(ConfigurationInterface::class);
        $this->logger = $this->prophesize(LoggerInterface::class);
        $this->clientFactory = new ClientFactory($this->configuration->reveal(), $this->logger->reveal());
    }

    /** @test */
    public function it_is_a_client_factory(): void
    {
        $this->assertInstanceOf(ClientFactory::class, $this->clientFactory);
    }

    /** @test */
    public function it_can_provide_a_client_for_test_environment(): void
    {
        $shop = $this->prophesize(Shop::class);
        $shop->getId()->willReturn('shop-id');

        $this->configuration->getMerchantAccount($shop)->willReturn($merchantAccount = 'mock-merchantAccount');
        $this->configuration->getApiKey($shop)->willReturn($apiKey = 'mock-apiKey');
        $this->configuration->getEnvironment($shop)->willReturn($environment = Environment::TEST);
        $this->configuration->getApiUrlPrefix($shop)->willReturn('api-url-prefix');

        $result = $this->clientFactory->provide($shop->reveal());

        $this->assertInstanceOf(Client::class, $result);
        $this->assertEquals($merchantAccount, $result->getConfig()->getMerchantAccount());
        $this->assertEquals($apiKey, $result->getConfig()->getXApiKey());
        $this->assertEquals($environment, $result->getConfig()->getEnvironment());
        $this->assertEquals(Client::ENDPOINT_TEST, $result->getConfig()->get('endpoint'));
        $this->assertEquals($this->logger->reveal(), $result->getLogger());
    }

    /** @test */
    public function it_can_provide_a_client_for_live_environment(): void
    {
        $shop = $this->prophesize(Shop::class);
        $shop->getId()->willReturn('shop-id');

        $this->configuration->getMerchantAccount($shop)->willReturn($merchantAccount = 'mock-merchantAccount');
        $this->configuration->getApiKey($shop)->willReturn($apiKey = 'mock-apiKey');
        $this->configuration->getEnvironment($shop)->willReturn($environment = Environment::LIVE);
        $this->configuration->getApiUrlPrefix($shop)->willReturn($urlPrefix = 'api-url-prefix');

        $result = $this->clientFactory->provide($shop->reveal());

        $this->assertInstanceOf(Client::class, $result);
        $this->assertEquals($merchantAccount, $result->getConfig()->getMerchantAccount());
        $this->assertEquals($apiKey, $result->getConfig()->getXApiKey());
        $this->assertEquals($environment, $result->getConfig()->getEnvironment());
        $expectedEndpoint = Client::ENDPOINT_PROTOCOL.$urlPrefix.Client::ENDPOINT_LIVE_SUFFIX;
        $this->assertEquals($expectedEndpoint, $result->getConfig()->get('endpoint'));
        $this->assertEquals($this->logger->reveal(), $result->getLogger());
    }
}
