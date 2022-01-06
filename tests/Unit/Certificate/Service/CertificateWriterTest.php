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
        $filesystem = $this->prophesize(Filesystem::class);

        $content = $this->certificateWriter->__invoke(
            'to',
            'file',
            'certificate content'
        );

        self::assertEquals('certificate content', $content);
    }
}
