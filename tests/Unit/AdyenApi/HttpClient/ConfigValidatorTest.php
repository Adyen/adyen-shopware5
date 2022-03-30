<?php

declare(strict_types=1);

namespace AdyenPayment\Tests\Unit\AdyenApi\HttpClient;

use Adyen\AdyenException;
use Adyen\Client;
use Adyen\Config;
use Adyen\Environment;
use Adyen\HttpClient\ClientInterface;
use AdyenPayment\AdyenApi\HttpClient\ClientFactoryInterface;
use AdyenPayment\AdyenApi\HttpClient\ConfigValidator;
use AdyenPayment\Components\ConfigurationInterface;
use AdyenPayment\Validator\ConstraintViolationFactory;
use Doctrine\Persistence\ObjectRepository;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Shopware\Models\Shop\Shop;
use Symfony\Component\Validator\ConstraintViolationList;

class ConfigValidatorTest extends TestCase
{
    use ProphecyTrait;
    private ConfigValidator $configValidator;

    /** @var ClientFactoryInterface|ObjectProphecy */
    private $adyenApiFactory;

    /** @var ConfigurationInterface|ObjectProphecy */
    private $configuration;

    /** @var ObjectProphecy|ObjectRepository */
    private $shopRepository;

    protected function setUp(): void
    {
        $this->adyenApiFactory = $this->prophesize(ClientFactoryInterface::class);
        $this->configuration = $this->prophesize(ConfigurationInterface::class);
        $this->shopRepository = $this->prophesize(ObjectRepository::class);

        $this->configValidator = new ConfigValidator(
            $this->adyenApiFactory->reveal(),
            $this->configuration->reveal(),
            $this->shopRepository->reveal()
        );
    }

    /** @test */
    public function it_is_a_config_validator(): void
    {
        $this->assertInstanceOf(ConfigValidator::class, $this->configValidator);
    }

    /** @test */
    public function it_will_return_a_violation_if_shop_is_not_found(): void
    {
        $this->shopRepository->find($shopId = 123456)->willReturn(null);

        $result = $this->configValidator->validate($shopId);

        $this->assertInstanceOf(ConstraintViolationList::class, $result);
        $this->assertCount(1, $result);
        $this->assertEquals(ConstraintViolationFactory::create('Shop not found for ID "'.$shopId.'".'), $result->get(0));
    }

    /** @test */
    public function it_will_return_a_violation_if_the_api_key_was_not_configured(): void
    {
        $shop = $this->prophesize(Shop::class);
        $shop->getId()->willReturn($shopId = 123456);
        $this->shopRepository->find($shopId)->willReturn($shop->reveal());

        $this->configuration->getApiKey($shop)->willReturn('');
        $this->configuration->getMerchantAccount($shop->reveal())->willReturn('merchantAccount');

        $result = $this->configValidator->validate($shopId);

        $this->assertInstanceOf(ConstraintViolationList::class, $result);
        $this->assertCount(1, $result);
        $this->assertEquals(ConstraintViolationFactory::create('Missing configuration: API key.'), $result->get(0));
    }

    /** @test */
    public function it_will_return_a_violation_if_the_merchant_account_was_not_configured(): void
    {
        $shop = $this->prophesize(Shop::class);
        $shop->getId()->willReturn($shopId = 123456);
        $this->shopRepository->find($shopId)->willReturn($shop->reveal());

        $this->configuration->getApiKey($shop->reveal())->willReturn('api-key');
        $this->configuration->getMerchantAccount($shop->reveal())->willReturn('');

        $this->assertEquals(
            new ConstraintViolationList([
                ConstraintViolationFactory::create('Missing configuration: merchant account.'),
            ]),
            $this->configValidator->validate($shopId)
        );
    }

    /** @test */
    public function it_will_return_a_violation_on_api_adyen_exception(): void
    {
        $shop = $this->prophesize(Shop::class);
        $shop->getId()->willReturn($shopId = 123456);
        $this->shopRepository->find($shopId)->willReturn($shop->reveal());

        $this->configuration->getApiKey($shop)->willReturn('api-key');
        $this->configuration->getMerchantAccount($shop)->willReturn('merchantAccount');
        $this->adyenApiFactory->provide($shop)->willThrow(AdyenException::class);

        $this->assertEquals(
            new ConstraintViolationList([
                ConstraintViolationFactory::create('Adyen API failed, check error logs'),
            ]),
            $this->configValidator->validate($shopId)
        );
    }

    /** @test */
    public function it_can_validate_a_config(): void
    {
        $shop = $this->prophesize(Shop::class);
        $shop->getId()->willReturn($shopId = 123456);

        $this->configuration->getApiKey($shop->reveal())->willReturn('api-key');
        $this->configuration->getMerchantAccount($shop->reveal())->willReturn($merchantAccount = 'merchantAccount');

        $client = $this->createClientMock();
        $this->shopRepository->find($shopId)->willReturn($shop->reveal());

        $this->adyenApiFactory->provide($shop->reveal())->willReturn($client->reveal());
        $this->configuration->getMerchantAccount($shop->reveal())->willReturn($merchantAccount);

        $this->assertEquals(new ConstraintViolationList(), $this->configValidator->validate($shopId));
    }

    private function createClientMock(): ObjectProphecy
    {
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
