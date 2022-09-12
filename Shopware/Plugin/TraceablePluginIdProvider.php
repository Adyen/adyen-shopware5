<?php

declare(strict_types=1);

namespace AdyenPayment\Shopware\Plugin;

use AdyenPayment\AdyenPayment;
use Psr\Log\LoggerInterface;
use Shopware\Bundle\PluginInstallerBundle\Service\InstallerService;

final class TraceablePluginIdProvider
{
    /** @var InstallerService */
    private $pluginManager;

    /** @var LoggerInterface */
    private $logger;

    public function __construct(InstallerService $pluginManager, LoggerInterface $logger)
    {
        $this->pluginManager = $pluginManager;
        $this->logger = $logger;
    }

    /**
     * @throws \Exception
     */
    public function provideId(): int
    {
        try {
            return $this->pluginManager->getPluginByName(AdyenPayment::NAME)->getId();
        } catch (\Exception $exception) {
            $this->logger->critical(
                'Could not provide the "id" of plugin "'.AdyenPayment::NAME.'"',
                ['exception' => $exception]
            );

            throw $exception;
        }
    }
}
