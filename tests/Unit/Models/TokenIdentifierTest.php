<?php

declare(strict_types=1);

namespace Unit\Models;

use AdyenPayment\Models\TokenIdentifier;
use PHPUnit\Framework\TestCase;

class TokenIdentifierTest extends TestCase
{
    /** @var TokenIdentifier */
    private $tokenIdentifier;

    protected function setUp(): void
    {
        $this->tokenIdentifier = TokenIdentifier::generateFromString('3a2ee0d3-adc0-4386-869d-429b6d5f1fa0');
    }

    /** @test */
    public function it_contains_a_token_identifier(): void
    {
        $this->assertInstanceOf(TokenIdentifier::class, $this->tokenIdentifier);
    }

    /** @test */
    public function it_knows_when_it_equals_token_identifier_objects(): void
    {
        $this->assertTrue($this->tokenIdentifier->equals(TokenIdentifier::generateFromString('3a2ee0d3-adc0-4386-869d-429b6d5f1fa0')));
        $this->assertFalse($this->tokenIdentifier->equals(TokenIdentifier::generate()));
    }

    /** @test */
    public function it_constructs_immutable(): void
    {
        $tokenIdentifier = TokenIdentifier::generateFromString('3a2ee0d3-adc0-4386-869d-429b6d5f1fa0');
        $this->assertEquals($this->tokenIdentifier, $tokenIdentifier);
        $this->assertNotSame($this->tokenIdentifier, $tokenIdentifier);
    }

    /** @test */
    public function it_can_be_constructed_from_string(): void
    {
        $tokenIdentifier = TokenIdentifier::generateFromString($expected = 'af55ecab-90db-4501-ba7d-9eef61ac3ee3');

        $this->assertEquals($expected, $tokenIdentifier->identifier());
    }

    /** @test */
    public function it_can_be_constructed_with_named_constructor(): void
    {
        $tokenIdentifier = TokenIdentifier::generate();

        $this->assertInstanceOf(TokenIdentifier::class, $tokenIdentifier);
    }
}
