<?php

declare(strict_types=1);

namespace AdyenPayment\Tests\Unit\AdyenApi\HttpClient;

use Adyen\Client;
use AdyenPayment\AdyenApi\HttpClient\ClientFactoryInterface;
use AdyenPayment\AdyenApi\HttpClient\ClientMemoise;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Shopware\Models\Shop\Shop;

class ClientMemoiseTest extends TestCase
{
    use ProphecyTrait;
    private ClientMemoise $clientMemoise;

    /** @var ClientFactoryInterface|ObjectProphecy */
    private $clientFactory;

    protected function setUp(): void
    {
        $this->clientFactory = $this->prophesize(ClientFactoryInterface::class);
        $this->clientMemoise = new ClientMemoise($this->clientFactory->reveal());
    }

    /** @test */
    public function it_is_a_client_memoise(): void
    {
        $this->assertInstanceOf(ClientMemoise::class, $this->clientMemoise);
    }

    /** @test */
    public function it_can_lookup_a_client(): void
    {
        $shop = new Shop();
        $client = $this->prophesize(Client::class);
        $this->clientFactory->provide($shop)->willReturn($client);

        $result = $this->clientMemoise->lookup($shop);

        $this->assertSame($client->reveal(), $result);
    }

    /** @test */
    public function it_can_return_a_memoised_client(): void
    {
        $shop = new Shop();
        $client = $this->prophesize(Client::class);

        $this->clientFactory->provide($shop)->shouldBeCalledOnce()->willReturn($client);

        $firstResult = $this->clientMemoise->lookup($shop);
        $result = $this->clientMemoise->lookup($shop);

        $this->assertSame($client->reveal(), $firstResult);
        $this->assertSame($client->reveal(), $result);
    }
}
