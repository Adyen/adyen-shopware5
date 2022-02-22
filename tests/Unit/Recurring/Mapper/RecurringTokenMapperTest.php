<?php

declare(strict_types=1);

namespace Unit\Recurring\Mapper;

use AdyenPayment\Exceptions\InvalidPaymentsResponseException;
use AdyenPayment\Recurring\Mapper\RecurringTokenMapper;
use AdyenPayment\Recurring\Mapper\RecurringTokenMapperInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;

class RecurringTokenMapperTest extends TestCase
{
    use ProphecyTrait;

    /** @var ObjectProphecy|RecurringTokenMapperInterface */
    private $recurringTokenMapper;

    protected function setUp(): void
    {
        $this->recurringTokenMapper = new RecurringTokenMapper();
    }

    /** @test */
    public function it_is_a_recurring_token_mapper(): void
    {
        $this->assertInstanceOf(RecurringTokenMapperInterface::class, $this->recurringTokenMapper);
    }

    /** @test */
    public function it_throws_invalid_payments_response_exception(): void
    {
        $this->expectException(InvalidPaymentsResponseException::class);
        $this->expectExceptionMessage('Payments response not found.');

        ($this->recurringTokenMapper)([]);
    }

    /** @test */
    public function it_can_map_from_array(): void
    {
        $adyenPaymentsResponseArray = [
            'additionalData' => [
                'recurring.recurringDetailReference' => '8415698462516992',
                'recurring.shopperReference' => 'YOUR_UNIQUE_SHOPPER_ID_IOfW3k9G2PvXFu2j',
            ],
            'pspReference' => '8515815919501547',
            'resultCode' => 'Authorised',
            'amount' => [
                'currency' => 'USD',
                'value' => 0,
            ],
            'merchantReference' => 'YOUR_ORDER_NUMBER',
        ];
        $recurringPaymentToken = ($this->recurringTokenMapper)($adyenPaymentsResponseArray);

        $this->assertEquals('YOUR_UNIQUE_SHOPPER_ID_IOfW3k9G2PvXFu2j', $recurringPaymentToken->customerId());
        $this->assertEquals('8415698462516992', $recurringPaymentToken->recurringDetailReference());
        $this->assertEquals('8515815919501547', $recurringPaymentToken->pspReference());
        $this->assertEquals('YOUR_ORDER_NUMBER', $recurringPaymentToken->orderNumber());
        $this->assertEquals('Authorised', $recurringPaymentToken->resultCode());
        $this->assertIsInt($recurringPaymentToken->amountValue());
        $this->assertEquals(0, $recurringPaymentToken->amountValue());
        $this->assertEquals('USD', $recurringPaymentToken->amountCurrency());
    }

    /** @test */
    public function it_can_map_default_values(): void
    {
        $adyenPaymentsResponseArray = [
            'additionalData' => [
            ],
            'amount' => [
            ],
        ];
        $recurringPaymentToken = ($this->recurringTokenMapper)($adyenPaymentsResponseArray);

        $this->assertEquals('', $recurringPaymentToken->customerId());
        $this->assertEquals('', $recurringPaymentToken->recurringDetailReference());
        $this->assertEquals('', $recurringPaymentToken->pspReference());
        $this->assertEquals('', $recurringPaymentToken->orderNumber());
        $this->assertEquals('Invalid', $recurringPaymentToken->resultCode());
        $this->assertEquals(0, $recurringPaymentToken->amountValue());
        $this->assertEquals('', $recurringPaymentToken->amountCurrency());
    }
}
