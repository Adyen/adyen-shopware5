<?php

declare(strict_types=1);

namespace AdyenPayment\Tests\Unit\Certificate\Filesystem;

use AdyenPayment\Certificate\Filesystem\CertificateWriter;
use AdyenPayment\Certificate\Filesystem\CertificateWriterInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;

class CertificateWriterTest extends TestCase
{
    private $certificateWriter;

    protected function setUp(): void
    {
        $this->certificateWriter = new CertificateWriter();
    }

    /** @test */
    public function it_is_a_certificate_writer(): void
    {
        $this->assertInstanceOf(CertificateWriterInterface::class, $this->certificateWriter);
    }

    /** @test */
    public function it_writes_content_to_file(): void
    {
        $filesystem = new Filesystem();

        $this->certificateWriter->__invoke(
            $toDir = 'tests/Integration/var/storage/temp/',
            $filename = 'file',
            'certificate content'
        );

        self::assertTrue($filesystem->exists($toDir));
        self::assertTrue($filesystem->exists($toDir.$filename));

        $filesystem->remove([
            'tests/Integration/var/storage/temp/file',
            'tests/Integration/var',
        ]);

        self::assertFalse($filesystem->exists($toDir));
        self::assertFalse($filesystem->exists('tests/Integration/var'));
    }
}
