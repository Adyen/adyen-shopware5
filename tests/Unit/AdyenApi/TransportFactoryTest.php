<?php

declare(strict_types=1);

namespace AdyenPayment\Tests\Unit\AdyenApi;

use Adyen\Service\Checkout;
use Adyen\Service\Recurring;
use AdyenPayment\AdyenApi\HttpClient\ClientFactoryInterface;
use AdyenPayment\AdyenApi\TransportFactory;
use AdyenPayment\AdyenApi\TransportFactoryInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Shopware\Models\Shop\Shop;

class TransportFactoryTest extends TestCase
{
    use ProphecyTrait;
    use ClientMockTrait;
    private TransportFactory $transportFactory;

    /** @var ClientFactoryInterface|ObjectProphecy */
    private $clientFactory;

    protected function setUp(): void
    {
        $this->clientFactory = $this->prophesize(ClientFactoryInterface::class);
        $this->transportFactory = new TransportFactory($this->clientFactory->reveal());
    }

    /** @test */
    public function it_is_a_transport_factory(): void
    {
        $this->assertInstanceOf(TransportFactoryInterface::class, $this->transportFactory);
    }

    /** @test */
    public function it_can_provide_a_recurring_client_service(): void
    {
        $shop = $this->prophesize(Shop::class);
        $adyenClient = $this->createClientMock();

        $this->clientFactory->provide($shop->reveal())->willReturn($adyenClient->reveal());

        $result = $this->transportFactory->recurring($shop->reveal());

        $this->assertInstanceOf(Recurring::class, $result);
    }

    /** @test */
    public function it_can_provide_a_checkout_client_service(): void
    {
        $shop = $this->prophesize(Shop::class);
        $adyenClient = $this->createClientMock();

        $this->clientFactory->provide($shop->reveal())->willReturn($adyenClient->reveal());

        $result = $this->transportFactory->checkout($shop->reveal());

        $this->assertInstanceOf(Checkout::class, $result);
    }
}
