<?php

declare(strict_types=1);

namespace Unit\Models\RecurringPayment;

use AdyenPayment\Models\RecurringPayment\ShopperInteraction;
use PHPUnit\Framework\TestCase;

class ShopperInteractionTest extends TestCase
{
    private ShopperInteraction $shopperInteraction;

    protected function setUp(): void
    {
        $this->shopperInteraction = ShopperInteraction::contAuth();
    }

    /** @test */
    public function it_contains_a_shopper_interaction(): void
    {
        $this->assertInstanceOf(ShopperInteraction::class, $this->shopperInteraction);
    }

    /** @test */
    public function it_can_compare_shopper_interaction_objects(): void
    {
        $this->assertTrue($this->shopperInteraction->equals(ShopperInteraction::contAuth()));
        $this->assertFalse($this->shopperInteraction->equals(ShopperInteraction::ecommerce()));
    }

    /** @test */
    public function it_checks_shopper_interaction_on_immutabillity(): void
    {
        $shopperInteractionContAuth = ShopperInteraction::contAuth();
        $this->assertEquals($this->shopperInteraction, $shopperInteractionContAuth);
        $this->assertNotSame($this->shopperInteraction, $shopperInteractionContAuth);
    }

    /**
     * @dataProvider shopperInteractionProvider
     * @test
     */
    public function it_can_be_constructed_with_named_constructors(ShopperInteraction $shopperInteraction, string $expected): void
    {
        $this->assertEquals($expected, $shopperInteraction->shopperInteraction());
    }

    public function shopperInteractionProvider(): \Generator
    {
        yield [ShopperInteraction::contAuth(), 'ContAuth'];
        yield [ShopperInteraction::ecommerce(), 'Ecommerce'];
    }

    /** @test  */
    public function it_throws_an_invalid_argument_exception_when_shopper_interaction_is_unknown(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid shopper interaction: "test"');

        ShopperInteraction::load('test');
    }

    /** @test  */
    public function it_can_load_a_shopper_interaction(): void
    {
        $result = ShopperInteraction::ecommerce();
        $this->assertEquals(ShopperInteraction::ecommerce(), $result);
    }
}
