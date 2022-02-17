<?php

declare(strict_types=1);

namespace Unit\Models\RecurringPayment;

use AdyenPayment\Models\PaymentResultCodes;
use AdyenPayment\Models\RecurringPayment\RecurringPaymentToken;
use AdyenPayment\Models\TokenIdentifier;
use PHPUnit\Framework\TestCase;
use Shopware\Components\Model\ModelEntity;

class RecurringPaymentTokenTest extends TestCase
{
    private RecurringPaymentToken $recurringPaymentToken;

    protected function setUp(): void
    {
        $this->recurringPaymentToken = RecurringPaymentToken::create(
            $tokenIdentifier = TokenIdentifier::generateFromString($knownUuid = '033a6dad-5a58-4b74-b420-6772bab3946e'),
            $customerId = 'YOUR_UNIQUE_SHOPPER_ID_IOfW3k9G2PvXFu2j',
            $recurringDetailReference = '8415698462516992',
            $pspReference = '8515815919501547',
            $orderNumber = 'YOUR_ORDER_NUMBER',
            $resultCode = PaymentResultCodes::authorised(),
            $amountValue = 10500,
            $amountCurrency = 'EUR'
        );
    }

    /** @test */
    public function it_is_a_model_entity(): void
    {
        $this->assertInstanceOf(ModelEntity::class, $this->recurringPaymentToken);
    }

    /** @test */
    public function it_contains_an_id(): void
    {
        $this->assertEquals('033a6dad-5a58-4b74-b420-6772bab3946e', $this->recurringPaymentToken->id());
    }

    /** @test */
    public function it_contains_a_token_identifier(): void
    {
        $this->assertEquals(
            TokenIdentifier::generateFromString('033a6dad-5a58-4b74-b420-6772bab3946e'),
            $this->recurringPaymentToken->tokenIdentifier()
        );
    }

    /** @test */
    public function it_contains_a_customer_id(): void
    {
        $this->assertEquals('YOUR_UNIQUE_SHOPPER_ID_IOfW3k9G2PvXFu2j', $this->recurringPaymentToken->customerId());
    }

    /** @test */
    public function it_contains_a_recurring_detail_reference(): void
    {
        $this->assertEquals('8415698462516992', $this->recurringPaymentToken->recurringDetailReference());
    }

    /** @test */
    public function it_contains_a_psp_reference(): void
    {
        $this->assertEquals('8515815919501547', $this->recurringPaymentToken->pspReference());
    }

    /** @test */
    public function it_contains_an_order_number(): void
    {
        $this->assertEquals('YOUR_ORDER_NUMBER', $this->recurringPaymentToken->orderNumber());
    }

    /** @test */
    public function it_contains_a_result_code(): void
    {
        $this->assertEquals('Authorised', $this->recurringPaymentToken->resultCode());
    }

    /** @test */
    public function it_contains_an_amount_value(): void
    {
        $this->assertEquals(10500, $this->recurringPaymentToken->amountValue());
    }

    /** @test */
    public function it_contains_an_amount_currency(): void
    {
        $this->assertEquals('EUR', $this->recurringPaymentToken->amountCurrency());
    }

    /** @test */
    public function it_contains_a_created_at_timestamp(): void
    {
        $createdAt = new \DateTimeImmutable();
        $this->recurringPaymentToken->setCreatedAt($createdAt);
        $this->assertInstanceOf(\DateTimeImmutable::class, $this->recurringPaymentToken->createdAt());
        $this->assertStringContainsString(
            $createdAt->format('d/m/y'),
            $this->recurringPaymentToken->createdAt()->format('d/m/y')
        );
    }

    /** @test */
    public function it_contains_an_updated_at_timestamp(): void
    {
        $updatedAt = new \DateTimeImmutable();
        $this->assertInstanceOf(\DateTimeImmutable::class, $this->recurringPaymentToken->updatedAt());
        $this->assertStringContainsString(
            $updatedAt->format('d/m/y'),
            $this->recurringPaymentToken->updatedAt()->format('d/m/y')
        );
    }

    /** @test */
    public function it_knows_when_it_is_a_one_off_payment(): void
    {
        $this->assertTrue($this->recurringPaymentToken->isOneOffPayment());
    }

    /** @test */
    public function it_knows_when_it_is_a_subscription(): void
    {
        $recurringPaymentTokenOrderNumberEmpty = RecurringPaymentToken::create(
            TokenIdentifier::generateFromString($uuid = 'f958e8a5-c707-4901-91dd-0e16b22b898c'),
            'YOUR_UNIQUE_SHOPPER_ID_IOfW3k9G2PvXFu2j',
            '8415698462516992',
            '8515815919501547',
            $orderNumber = '',
            PaymentResultCodes::authorised(),
            10500,
            'EUR'
        );
        $this->assertTrue($recurringPaymentTokenOrderNumberEmpty->isSubscription());
    }
}
