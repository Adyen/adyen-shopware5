<?php

declare(strict_types=1);

namespace AdyenPayment\Tests\Integration\Certificate\Filesystem;

use AdyenPayment\Certificate\Exception\CouldNotReadCertificate;
use AdyenPayment\Certificate\Filesystem\CertificateReader;
use AdyenPayment\Certificate\Filesystem\CertificateReaderInterface;
use AdyenPayment\Certificate\Filesystem\CertificateWriter;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;

class CertificateReaderTest extends TestCase
{
    private $certificateReader;
    private $certificateWriter;

    protected function setUp(): void
    {
        $this->certificateReader = new CertificateReader();
        $this->certificateWriter = new CertificateWriter();
    }

    /** @test */
    public function it_is_a_certificate_reader(): void
    {
        $this->assertInstanceOf(CertificateReaderInterface::class, $this->certificateReader);
    }

    /** @test */
    public function it_reads_content_from_file(): void
    {
        $this->certificateWriter->__invoke('test-content');
        $content = $this->certificateReader->__invoke();

        self::assertEquals('test-content', $content->certificate());
    }

    /** @test */
    public function it_cannot_read_content_from_file(): void
    {
        $filesystem = new Filesystem();

        $filesystem->remove([
            '.well-known/apple-developer-merchantid-domain-association',
            '.well-known',
        ]);

        $this->expectException(CouldNotReadCertificate::class);
        $this->expectExceptionMessage(
            'Could not read certificate from "/app/custom/plugins/AdyenPayment/Certificate/Filesystem/../../../../../custom/plugins/AdyenPayment/.well-known/apple-developer-merchantid-domain-association"'
        );

        $this->certificateReader->__invoke();
    }
}
