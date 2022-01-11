<?php

declare(strict_types=1);

namespace AdyenPayment\Tests\Integration\Certificate\Filesystem;

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

        $this->certificateWriter->__invoke('certificate content');

        self::assertTrue($filesystem->exists('.well-known'));
        self::assertTrue($filesystem->exists('.well-known/apple-developer-merchantid-domain-association'));
        self::assertEquals(
            'certificate content',
            file_get_contents('.well-known/apple-developer-merchantid-domain-association')
        );

        $filesystem->remove([
            '.well-known/apple-developer-merchantid-domain-association',
            '.well-known',
        ]);
    }
}
