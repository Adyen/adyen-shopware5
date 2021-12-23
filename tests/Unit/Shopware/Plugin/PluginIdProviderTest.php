<?php

declare(strict_types=1);

namespace AdyenPayment\Tests\Unit\Shopware\Plugin;

use AdyenPayment\AdyenPayment;
use AdyenPayment\Shopware\Plugin\TraceablePluginIdProvider;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Log\LoggerInterface;
use Shopware\Bundle\PluginInstallerBundle\Service\InstallerService;
use Shopware\Models\Plugin\Plugin;

final class PluginIdProviderTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var InstallerService|\Prophecy\Prophecy\ObjectProphecy
     */
    private $pluginManager;
    private TraceablePluginIdProvider $provider;

    protected function setUp(): void
    {
        $this->pluginManager = $this->prophesize(InstallerService::class);
        $this->logger = $this->prophesize(LoggerInterface::class);
        $this->provider = new TraceablePluginIdProvider(
            $this->pluginManager->reveal(),
            $this->logger->reveal()
        );
    }

    /** @test */
    public function it_can_provide_plugin_id(): void
    {
        $plugin = new Plugin();
        $plugin->setId($id = 3633);
        $this->pluginManager->getPluginByName('AdyenPayment')->willReturn($plugin);
        $this->logger->critical(Argument::cetera())->shouldNotBeCalled();

        $result = $this->provider->provideId();
        $this->assertEquals($id, $result);
    }

    /** @test */
    public function it_logs_and_throws_exception(): void
    {
        $this->pluginManager->getPluginByName(Argument::cetera())
            ->willThrow($exception = new \Exception($message = 'Some Unknown plugin'));
        $this->logger->critical(
            'Could not provide the "id" of plugin "'.AdyenPayment::NAME.'"',
            ['exception' => $exception]
        )->shouldBeCalled();

        $this->expectExceptionObject($exception);
        $this->provider->provideId();
    }
}
