<?php

declare(strict_types=1);

namespace AdyenPayment\Tests\Unit\Components\Payload\Providers;

use AdyenPayment\Components\Payload\PaymentContext;
use AdyenPayment\Components\Payload\PaymentPayloadProvider;
use AdyenPayment\Components\Payload\Providers\RecurringPaymentProvider;
use AdyenPayment\Models\RecurringPayment\RecurringProcessingModel;
use AdyenPayment\Models\RecurringPayment\ShopperInteraction;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;

final class RecurringPaymentProviderTest extends TestCase
{
    use ProphecyTrait;
    private RecurringPaymentProvider $recurringPaymentProvider;

    /** @var ObjectProphecy|PaymentContext */
    private $paymentContext;

    protected function setUp(): void
    {
        $this->recurringPaymentProvider = new RecurringPaymentProvider();
        $this->paymentContext = $this->prophesize(PaymentContext::class);
    }

    /** @test */
    public function it_is_a_recurring_payment_payload_provider(): void
    {
        self::assertInstanceOf(PaymentPayloadProvider::class, $this->recurringPaymentProvider);
    }

    /** @test */
    public function it_will_return_empty_for_none_stored_payment_method(): void
    {
        $this->paymentContext->getPaymentInfo()->willReturn([]);

        $result = $this->recurringPaymentProvider->provide($this->paymentContext->reveal());

        self::assertEquals([], $result);
    }

    /** @test */
    public function it_can_return_the_recurring_one_off_payment_token_data(): void
    {
        $this->paymentContext->getPaymentInfo()->willReturn(['storedPaymentMethodId' => 'stored-method-id']);

        $result = $this->recurringPaymentProvider->provide($this->paymentContext->reveal());

        self::assertEquals([
            'shopperInteraction' => ShopperInteraction::contAuth()->shopperInteraction(),
            'recurringProcessingModel' => RecurringProcessingModel::cardOnFile()->recurringProcessingModel(),
        ], $result);
    }
}
