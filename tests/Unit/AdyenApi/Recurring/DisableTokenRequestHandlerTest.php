<?php

declare(strict_types=1);

namespace AdyenPayment\Tests\Unit\AdyenApi;

use Adyen\Service\Recurring;
use AdyenPayment\AdyenApi\Model\ApiResponse;
use AdyenPayment\AdyenApi\Recurring\DisableTokenRequestHandler;
use AdyenPayment\AdyenApi\Recurring\DisableTokenRequestHandlerInterface;
use AdyenPayment\AdyenApi\TransportFactoryInterface;
use AdyenPayment\Components\Adyen\PaymentMethodServiceInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Shopware\Models\Shop\Shop;

class DisableTokenRequestHandlerTest extends TestCase
{
    use ProphecyTrait;
    private DisableTokenRequestHandler $disableTokenRequestHandler;

    /** @var ObjectProphecy|PaymentMethodServiceInterface */
    private $paymentMethodService;

    /** @var ObjectProphecy|TransportFactoryInterface */
    private $transportFactory;

    protected function setUp(): void
    {
        $this->paymentMethodService = $this->prophesize(PaymentMethodServiceInterface::class);
        $this->transportFactory = $this->prophesize(TransportFactoryInterface::class);

        $this->disableTokenRequestHandler = new DisableTokenRequestHandler(
            $this->paymentMethodService->reveal(),
            $this->transportFactory->reveal()
        );
    }

    /** @test */
    public function it_is_a_disable_token_request_handler(): void
    {
        $this->assertInstanceOf(DisableTokenRequestHandlerInterface::class, $this->disableTokenRequestHandler);
    }

    /** @test */
    public function it_will_return_null_on_missing_customer_number(): void
    {
        $shop = $this->prophesize(Shop::class);
        $this->paymentMethodService->provideCustomerNumber()->willReturn('');

        $result = $this->disableTokenRequestHandler->disableToken('recurringTokenId', $shop->reveal());

        $this->assertNull($result);
    }

    /** @test */
    public function it_can_disable_a_token(): void
    {
        $shop = $this->prophesize(Shop::class);
        $recurringTransport = $this->prophesize(Recurring::class);
        $payload = [
            'shopperReference' => $customerNumber = 'customer-number',
            'recurringDetailReference' => $recurringTokenId = 'recurringTokenId',
        ];
        $recurringTransport->disable($payload)->willReturn($expected = [
            'statusCode' => $statusCode = 200,
            'success' => $success = true,
            'message' => $message = 'It worked',
        ]);
        $this->paymentMethodService->provideCustomerNumber()->willReturn($customerNumber);
        $this->transportFactory->recurring($shop->reveal())->willReturn($recurringTransport);

        $result = $this->disableTokenRequestHandler->disableToken($recurringTokenId, $shop->reveal());

        $this->assertEquals(ApiResponse::create($statusCode, $success, $message), $result);
    }
}
