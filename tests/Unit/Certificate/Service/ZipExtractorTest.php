<?php

declare(strict_types=1);

namespace AdyenPayment\Tests\Unit\Certificate\Service;

use AdyenPayment\Certificate\Service\ZipExtractor;
use AdyenPayment\Certificate\Service\ZipExtractorInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Log\LoggerInterface;
use Symfony\Component\Filesystem\Filesystem;

class ZipExtractorTest extends TestCase
{
    use ProphecyTrait;

    /** @var LoggerInterface|ObjectProphecy */
    private $logger;
    private $zipExtractor;

    protected function setUp(): void
    {
        $this->logger = $this->prophesize(LoggerInterface::class);
        $this->zipExtractor = new ZipExtractor(
            $this->logger->reveal()
        );
    }

    /** @test */
    public function it_is_a_zip_extractor(): void
    {
        $this->assertInstanceOf(ZipExtractorInterface::class, $this->zipExtractor);
    }

    /** @test */
    public function it_writes_content_to_file(): void
    {
        $filesystem = new Filesystem();

        $content = $this->zipExtractor->__invoke(
            $fromDir = 'tests/Integration/Fixtures',
            $toDir = 'tests/Integration/var/storage/temp/.well-known',
            $filename = 'ZipExtractor',
            $extension = '.zip'
        );

        self::assertStringContainsString('zip-extractor-content', $content);
        self::assertTrue($filesystem->exists($fromDir));
        self::assertTrue($filesystem->exists($toDir));
        self::assertTrue($filesystem->exists($toDir.'/'.$filename));

        $filesystem->remove([
            'tests/Integration/var/storage/temp/.well-known/ZipExtractor',
            'tests/Integration/var',
        ]);

        self::assertFalse($filesystem->exists($toDir));
        self::assertFalse($filesystem->exists('tests/Integration/var'));
    }
}
