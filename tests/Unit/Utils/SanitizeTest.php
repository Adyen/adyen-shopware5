<?php

declare(strict_types=1);

namespace AdyenPayment\Tests\Unit\Utils;

use AdyenPayment\Utils\Sanitize;
use PHPUnit\Framework\TestCase;

final class SanitizeTest extends TestCase
{
    /** @test */
    public function it_can_remove_non_word(): void
    {
        $result = Sanitize::removeNonWord('This is a 1st_test. ? &%');

        $this->assertEquals('This_is_a_1st_test', $result);
    }

    /** @test */
    public function it_can_escape_without_quotes(): void
    {
        $result = Sanitize::escape("<a href='test'>Test</a>");

        $this->assertEquals("&lt;a href='test'&gt;Test&lt;/a&gt;", $result);
    }
}
