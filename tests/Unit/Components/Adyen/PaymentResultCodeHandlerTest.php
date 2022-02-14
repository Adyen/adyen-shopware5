<?php

declare(strict_types=1);

namespace Unit\Components\Adyen;

use AdyenPayment\Components\Adyen\PaymentResultCodeHandler;
use AdyenPayment\Components\Adyen\PaymentResultCodeHandlerInterface;
use AdyenPayment\Components\BasketService;
use AdyenPayment\Models\PaymentResultCodes;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;

class PaymentResultCodeHandlerTest extends TestCase
{
    use ProphecyTrait;

    /** @var BasketService|ObjectProphecy BasketService */
    private $basketService;
    private PaymentResultCodeHandlerInterface $paymentResultCodeHandler;

    protected function setUp(): void
    {
        $this->basketService = $this->prophesize(BasketService::class);
        $this->paymentResultCodeHandler = new PaymentResultCodeHandler(
            $this->basketService->reveal()
        );
    }

    /** @test */
    public function it_is_instance_of_payment_result_code_handler_interface(): void
    {
        $this->assertInstanceOf(
            PaymentResultCodeHandlerInterface::class,
            $this->paymentResultCodeHandler
        );
    }

    /**
     * @dataProvider paymentResponseInfoProvider
     * @test
     */
    public function it_can_handle_a_known_payment_result_code(array $paymentResponseInfo): void
    {
        ($this->paymentResultCodeHandler)($paymentResponseInfo);
        $this->basketService->cancelAndRestoreByOrderNumber(Argument::cetera())->shouldNotBeCalled();
    }

    public function paymentResponseInfoProvider(): \Generator
    {
        yield [['resultCode' => PaymentResultCodes::authorised()->resultCode()]];
        yield [['resultCode' => PaymentResultCodes::cancelled()->resultCode()]];
        yield [['resultCode' => PaymentResultCodes::challengeShopper()->resultCode()]];
        yield [['resultCode' => PaymentResultCodes::error()->resultCode()]];
        yield [['resultCode' => PaymentResultCodes::identifyShopper()->resultCode()]];
        yield [['resultCode' => PaymentResultCodes::pending()->resultCode()]];
        yield [['resultCode' => PaymentResultCodes::received()->resultCode()]];
        yield [['resultCode' => PaymentResultCodes::redirectShopper()->resultCode()]];
        yield [['resultCode' => PaymentResultCodes::refused()->resultCode()]];
    }

    /** @test */
    public function it_will_cancel_and_restore_order_number_when_merchant_reference_is_given(): void
    {
        $paymentResponseInfo = [
            'resultCode' => 'NOT_KNOWN_RESULT_CODE',
            'merchantReference' => '012345',
        ];
        ($this->paymentResultCodeHandler)($paymentResponseInfo);
        $this->basketService->cancelAndRestoreByOrderNumber($paymentResponseInfo['merchantReference'])->shouldBeCalledOnce();
    }

    /** @test */
    public function it_will_not_cancel_and_restore_order_number_when_no_merchant_reference_is_given(): void
    {
        $paymentResponseInfo = [
            'resultCode' => 'NOT_KNOWN_RESULT_CODE',
        ];
        ($this->paymentResultCodeHandler)($paymentResponseInfo);
        $this->basketService->cancelAndRestoreByOrderNumber(Argument::cetera())->shouldNotBeCalled();
    }
}
