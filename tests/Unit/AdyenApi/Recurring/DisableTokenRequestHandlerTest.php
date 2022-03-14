<?php

declare(strict_types=1);

namespace AdyenPayment\Tests\Unit\AdyenApi\Recurring;

use Adyen\Service\Recurring;
use AdyenPayment\AdyenApi\Model\ApiResponse;
use AdyenPayment\AdyenApi\Recurring\DisableTokenRequestHandler;
use AdyenPayment\AdyenApi\Recurring\DisableTokenRequestHandlerInterface;
use AdyenPayment\AdyenApi\TransportFactoryInterface;
use AdyenPayment\Session\CustomerNumberProviderInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Shopware\Models\Shop\Shop;

class DisableTokenRequestHandlerTest extends TestCase
{
    use ProphecyTrait;
    private DisableTokenRequestHandler $disableTokenRequestHandler;

    /** @var CustomerNumberProviderInterface|ObjectProphecy */
    private $customerNumberProvider;

    /** @var ObjectProphecy|TransportFactoryInterface */
    private $transportFactory;

    protected function setUp(): void
    {
        $this->customerNumberProvider = $this->prophesize(CustomerNumberProviderInterface::class);
        $this->transportFactory = $this->prophesize(TransportFactoryInterface::class);

        $this->disableTokenRequestHandler = new DisableTokenRequestHandler(
            $this->transportFactory->reveal(),
            $this->customerNumberProvider->reveal()
        );
    }

    /** @test */
    public function it_is_a_disable_token_request_handler(): void
    {
        $this->assertInstanceOf(DisableTokenRequestHandlerInterface::class, $this->disableTokenRequestHandler);
    }

    /** @test */
    public function it_will_return_a_400_on_missing_customer_number(): void
    {
        $shop = $this->prophesize(Shop::class);
        $this->customerNumberProvider->__invoke()->willReturn('');
        $this->transportFactory->recurring(Argument::any())->shouldNotBeCalled();

        $result = $this->disableTokenRequestHandler->disableToken('recurringTokenId', $shop->reveal());

        $this->assertEquals(ApiResponse::empty(), $result);
    }

    /** @test */
    public function it_will_return_an_api_response_for_disable_token_success(): void
    {
        $shop = $this->prophesize(Shop::class);
        $recurringTransport = $this->prophesize(Recurring::class);
        $payload = [
            'shopperReference' => $customerNumber = 'customer-number',
            'recurringDetailReference' => $recurringTokenId = 'recurringTokenId',
        ];
        $recurringTransport->disable($payload)->willReturn([
            'status' => $statusCode = 200,
            'message' => $message = 'successfully-disabled',
        ]);
        $this->customerNumberProvider->__invoke()->willReturn($customerNumber);
        $this->transportFactory->recurring($shop->reveal())->willReturn($recurringTransport);

        $result = $this->disableTokenRequestHandler->disableToken($recurringTokenId, $shop->reveal());

        $this->assertEquals(ApiResponse::create($statusCode, true, $message), $result);
    }

    /** @test */
    public function it_will_return_an_api_response_for_disable_token_error(): void
    {
        $shop = $this->prophesize(Shop::class);
        $recurringTransport = $this->prophesize(Recurring::class);
        $payload = [
            'shopperReference' => $customerNumber = 'customer-number',
            'recurringDetailReference' => $recurringTokenId = 'recurringTokenId',
        ];
        $recurringTransport->disable($payload)->willReturn([
            'status' => $statusCode = 422,
            'message' => $message = 'PaymentDetail not found',
        ]);
        $this->customerNumberProvider->__invoke()->willReturn($customerNumber);
        $this->transportFactory->recurring($shop->reveal())->willReturn($recurringTransport);

        $result = $this->disableTokenRequestHandler->disableToken($recurringTokenId, $shop->reveal());

        $this->assertEquals(ApiResponse::create($statusCode, false, $message), $result);
    }
}
