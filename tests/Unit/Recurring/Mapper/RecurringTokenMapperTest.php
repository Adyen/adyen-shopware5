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
    private string $adyenPaymentsResponseJson;

    /** @var ObjectProphecy|RecurringTokenMapperInterface */
    private $recurringTokenMapper;

    protected function setUp(): void
    {
        $this->adyenPaymentsResponseJson = '{
          "additionalData": {
            "recurring.recurringDetailReference": "8415698462516992",
            "recurring.shopperReference": "YOUR_UNIQUE_SHOPPER_ID_IOfW3k9G2PvXFu2j"
          },
          "pspReference": "8515815919501547",
          "resultCode": "Authorised",
          "amount": {
            "currency": "USD",
            "value": 0
          },
          "merchantReference": "YOUR_ORDER_NUMBER"
        }';

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
        $recurringPaymentToken = ($this->recurringTokenMapper)(json_decode($this->adyenPaymentsResponseJson, true));

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
    public function it_maps_empty_string_when_customer_id_not_in_response(): void
    {
        $adyenResponse = json_decode($this->adyenPaymentsResponseJson, true);
        unset($adyenResponse['additionalData']['recurring.shopperReference']);
        $recurringPaymentToken = ($this->recurringTokenMapper)($adyenResponse);

        $this->assertEquals('', $recurringPaymentToken->customerId());
    }

    /** @test */
    public function it_maps_empty_string_when_recurring_detail_reference_not_in_response(): void
    {
        $adyenResponse = json_decode($this->adyenPaymentsResponseJson, true);
        unset($adyenResponse['additionalData']['recurring.recurringDetailReference']);
        $recurringPaymentToken = ($this->recurringTokenMapper)($adyenResponse);

        $this->assertEquals('', $recurringPaymentToken->recurringDetailReference());
    }

    /** @test */
    public function it_maps_empty_string_when_additional_data_not_in_response(): void
    {
        $adyenResponse = json_decode($this->adyenPaymentsResponseJson, true);
        unset($adyenResponse['additionalData']);
        $recurringPaymentToken = ($this->recurringTokenMapper)($adyenResponse);

        $this->assertEquals('', $recurringPaymentToken->customerId());
        $this->assertEquals('', $recurringPaymentToken->recurringDetailReference());
    }

    /** @test */
    public function it_maps_empty_string_when_psp_reference_not_in_response(): void
    {
        $adyenResponse = json_decode($this->adyenPaymentsResponseJson, true);
        unset($adyenResponse['pspReference']);
        $recurringPaymentToken = ($this->recurringTokenMapper)($adyenResponse);

        $this->assertEquals('', $recurringPaymentToken->pspReference());
    }

    /** @test */
    public function it_maps_empty_string_when_merchant_reference_not_in_response(): void
    {
        $adyenResponse = json_decode($this->adyenPaymentsResponseJson, true);
        unset($adyenResponse['merchantReference']);
        $recurringPaymentToken = ($this->recurringTokenMapper)($adyenResponse);

        $this->assertEquals('', $recurringPaymentToken->orderNumber());
    }

    /** @test */
    public function it_maps_refused_result_code_when_result_code_not_in_response(): void
    {
        $adyenResponse = json_decode($this->adyenPaymentsResponseJson, true);
        unset($adyenResponse['resultCode']);
        $recurringPaymentToken = ($this->recurringTokenMapper)($adyenResponse);

        $this->assertEquals('Refused', $recurringPaymentToken->resultCode());
    }

    /** @test */
    public function it_maps_to_zero_when_amount_value_not_in_response(): void
    {
        $adyenResponse = json_decode($this->adyenPaymentsResponseJson, true);
        unset($adyenResponse['amount']['value']);
        $recurringPaymentToken = ($this->recurringTokenMapper)($adyenResponse);

        $this->assertEquals(0, $recurringPaymentToken->amountValue());
    }

    /** @test */
    public function it_maps_to_empty_string_when_amount_currency_not_in_response(): void
    {
        $adyenResponse = json_decode($this->adyenPaymentsResponseJson, true);
        unset($adyenResponse['amount']['currency']);
        $recurringPaymentToken = ($this->recurringTokenMapper)($adyenResponse);

        $this->assertEquals('', $recurringPaymentToken->amountCurrency());
    }

    /** @test */
    public function it_maps_to_empty_string_and_zero_when_amount_not_in_response(): void
    {
        $adyenResponse = json_decode($this->adyenPaymentsResponseJson, true);
        unset($adyenResponse['amount']);
        $recurringPaymentToken = ($this->recurringTokenMapper)($adyenResponse);

        $this->assertEquals(0, $recurringPaymentToken->amountValue());
        $this->assertEquals('', $recurringPaymentToken->amountCurrency());
    }
}
