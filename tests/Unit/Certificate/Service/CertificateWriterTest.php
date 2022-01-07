<?php

declare(strict_types=1);

namespace AdyenPayment\Tests\Unit\Certificate\Service;

use AdyenPayment\Certificate\Service\CertificateWriter;
use AdyenPayment\Certificate\Service\CertificateWriterInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Log\LoggerInterface;
use Symfony\Component\Filesystem\Filesystem;

class CertificateWriterTest extends TestCase
{
    use ProphecyTrait;

    /** @var LoggerInterface|ObjectProphecy */
    private $logger;
    private $certificateWriter;

    protected function setUp(): void
    {
        $this->logger = $this->prophesize(LoggerInterface::class);
        $this->certificateWriter = new CertificateWriter(
            $this->logger->reveal()
        );
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

        $content = $this->certificateWriter->__invoke(
            $toDir = 'tests/Integration/var/storage/temp/',
            $filename = 'file',
            'certificate content'
        );

        self::assertEquals('certificate content', $content);
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
