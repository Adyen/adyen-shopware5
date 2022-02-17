<?php

declare(strict_types=1);

namespace Unit\Models;

use AdyenPayment\Models\TokenIdentifier;
use PHPUnit\Framework\TestCase;

class TokenIdentifierTest extends TestCase
{
    private TokenIdentifier $tokenIdentifier;
    private string $knownUuid = '3a2ee0d3-adc0-4386-869d-429b6d5f1fa0';

    protected function setUp(): void
    {
        $this->tokenIdentifier = TokenIdentifier::generateFromString($this->knownUuid);
    }

    /** @test */
    public function it_contains_a_token_identifier(): void
    {
        $this->assertInstanceOf(TokenIdentifier::class, $this->tokenIdentifier);
    }

    /** @test */
    public function it_can_compare_token_identifier_objects(): void
    {
        $this->assertTrue($this->tokenIdentifier->equals(TokenIdentifier::generateFromString($this->knownUuid)));
        $this->assertFalse($this->tokenIdentifier->equals(TokenIdentifier::generate()));
    }

    /** @test */
    public function it_checks_token_identifier_on_immutabillity(): void
    {
        $tokenIdentifier = TokenIdentifier::generateFromString($this->knownUuid);
        $this->assertEquals($this->tokenIdentifier, $tokenIdentifier);
        $this->assertNotSame($this->tokenIdentifier, $tokenIdentifier);
    }

    /**
     * @dataProvider tokenIdentifierProvider
     * @test
     */
    public function it_can_be_constructed_with_named_constructors(TokenIdentifier $tokenIdentifier, string $expected): void
    {
        $this->assertEquals($expected, $tokenIdentifier->identifier());
    }

    public function tokenIdentifierProvider(): \Generator
    {
        yield [TokenIdentifier::generateFromString('af55ecab-90db-4501-ba7d-9eef61ac3ee3'), 'af55ecab-90db-4501-ba7d-9eef61ac3ee3'];
        yield [$randomGenerated = TokenIdentifier::generate(), $randomGenerated->identifier()];
    }
}
