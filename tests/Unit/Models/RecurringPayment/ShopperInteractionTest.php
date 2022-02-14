<?php

declare(strict_types=1);

namespace Unit\Models\RecurringPayment;

use AdyenPayment\Models\RecurringPayment\ShopperInteraction;
use PHPUnit\Framework\TestCase;

class ShopperInteractionTest extends TestCase
{
    /** @test */
    public function it_can_construct_through_named_constructor(): void
    {
        $this->assertInstanceOf(ShopperInteraction::class, ShopperInteraction::contAuth());
    }

    /**
     * @dataProvider shopperInteractionProvider
     * @test
     */
    public function it_contains_shopper_interaction(ShopperInteraction $shopperInteraction, string $expected): void
    {
        $this->assertEquals($expected, $shopperInteraction->value());
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
    public function it_can_check_it_is_equal_to_another_value_object(): void
    {
        $this->assertTrue(ShopperInteraction::ecommerce()->equals(ShopperInteraction::ecommerce()));
    }

    /** @test  */
    public function it_can_check_it_is_not_equal_to_another_value_object(): void
    {
        $this->assertFalse(ShopperInteraction::ecommerce()->equals(ShopperInteraction::contAuth()));
    }

    /** @test  */
    public function it_can_load_a_shopper_interaction(): void
    {
        $this->assertEquals(
            ShopperInteraction::ecommerce(),
            ShopperInteraction::load('Ecommerce')
        );
    }
}
