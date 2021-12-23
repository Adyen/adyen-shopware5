<?php

declare(strict_types=1);

namespace AdyenPayment\Tests\Unit\Models\Payment;

use AdyenPayment\Models\Payment\PaymentGroup;
use PHPUnit\Framework\TestCase;

final class PaymentGroupTest extends TestCase
{
    private PaymentGroup $group;

    protected function setUp(): void
    {
        $this->group = PaymentGroup::default();
    }

    /** @test */
    public function it_contains_a_group(): void
    {
        $this->assertEquals('payment', $this->group->group());
    }

    /** @test */
    public function it_knows_it_equals_default_group(): void
    {
        $this->assertTrue($this->group->equals(PaymentGroup::default()));
    }

    /** @test */
    public function it_can_be_constructed_by_stored(): void
    {
        $group = PaymentGroup::stored();
        $this->assertEquals('stored', $group->group());
        $this->assertTrue($group->equals(PaymentGroup::stored()));
    }
}
