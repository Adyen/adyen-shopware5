<?php

declare(strict_types=1);

namespace AdyenPayment\Tests\Unit\Models\PaymentMethod;

use AdyenPayment\Models\Enum\PaymentMethod\ImportStatus;
use AdyenPayment\Models\Payment\PaymentMethod;
use AdyenPayment\Models\PaymentMethod\ImportResult;
use PHPUnit\Framework\TestCase;
use Shopware\Models\Shop\Shop;

final class ImportResultTest extends TestCase
{
    /** @var ImportResult */
    private $importResult;

    protected function setUp(): void
    {
        $this->importResult = ImportResult::success(
            new Shop(),
            PaymentMethod::fromRaw([]),
            ImportStatus::created()
        );
    }

    /** @test */
    public function it_contains_a_shop(): void
    {
        $this->assertEquals(new Shop(), $this->importResult->getShop());
    }

    /** @test */
    public function it_contains_success(): void
    {
        $this->assertTrue($this->importResult->isSuccess());
    }

    /** @test */
    public function it_contains_a_payment_method(): void
    {
        $this->assertEquals(PaymentMethod::fromRaw([]), $this->importResult->getPaymentMethod());
    }

    /** @test */
    public function it_contains_a_exception(): void
    {
        $this->assertNull($this->importResult->getException());
    }

    /** @test */
    public function it_contains_a_status(): void
    {
        $this->assertEquals(ImportStatus::created(), $this->importResult->getStatus());
    }

    /** @test */
    public function it_can_be_constructed_by_success_sub_shop_fallback(): void
    {
        $result = ImportResult::successSubShopFallback(
            $shop = new Shop(),
            $status = ImportStatus::updated()
        );

        $this->assertSame($shop, $result->getShop());
        $this->assertTrue($result->isSuccess());
        $this->assertNull($result->getPaymentMethod());
        $this->assertNull($result->getException());
        $this->assertSame($status, $result->getStatus());
    }

    /** @test */
    public function it_can_be_constructed_from_exception(): void
    {
        $result = ImportResult::fromException(
            $shop = new Shop(),
            $paymentMethod = PaymentMethod::fromRaw([]),
            $exception = new \Exception('message')
        );

        $this->assertSame($shop, $result->getShop());
        $this->assertFalse($result->isSuccess());
        $this->assertEquals($paymentMethod, $result->getPaymentMethod());
        $this->assertEquals($exception, $result->getException());
        $this->assertEquals(ImportStatus::notHandledStatus(), $result->getStatus());
    }
}
