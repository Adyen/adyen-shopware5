<?php

declare(strict_types=1);

namespace AdyenPayment\Tests\Unit\Applepay\Exception;

use AdyenPayment\Applepay\Exception\FileNotWrittenException;
use PHPUnit\Framework\TestCase;

class FileNotWrittenExceptionTest extends TestCase
{
    protected function setUp(): void
    {
        $this->exception = new FileNotWrittenException();
    }

    /** @test */
    public function it_is_a_runtime_exception(): void
    {
        $this->assertInstanceOf(\RuntimeException::class, $this->exception);
    }

    /** @test */
    public function it_can_be_constructed_with_file_path(): void
    {
        $exception = $this->exception::withFilepath($filepath = 'path/to/file');

        $this->assertInstanceOf(FileNotWrittenException::class, $exception);
        $this->assertNotSame($this->exception, $exception);
        $this->assertEquals('Could not write apple pay association file, path: "'.$filepath.'"',
            $exception->getMessage());
    }
}
