<?php

declare(strict_types=1);

namespace AdyenPayment\Tests\Unit\Certificate\Service;

use AdyenPayment\Certificate\Service\ZipExtractor;
use AdyenPayment\Certificate\Service\ZipExtractorInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Log\LoggerInterface;

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

    // TODO: fix
//    /** @test */
//    public function it_writes_content_to_file(): void
//    {
//        $content = $this->zipExtractor->__invoke(
//            'var/storage/apple/archive',
//            '.well-known',
//            'apple-developer-merchantid-domain-association',
//            '.zip'
//        );
//
//        $zip = $this->prophesize(\ZipArchive::class)->reveal();
//        $zip->extractTo('.well-known')->shouldBeCalledOnce()->willReturn(true);
//        self::assertEquals('certificate content', $content);
//    }
}
