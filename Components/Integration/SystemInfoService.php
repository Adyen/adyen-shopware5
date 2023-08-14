<?php

namespace AdyenPayment\Components\Integration;

use Adyen\Core\BusinessLogic\Domain\Configuration\Configuration;
use Adyen\Core\BusinessLogic\Domain\InfoSettings\Models\SystemInfo;
use Adyen\Core\BusinessLogic\Domain\Integration\SystemInfo\SystemInfoService as SystemInfoServiceInterface;
use AdyenPayment\Components\Configuration\ConfigurationService;
use AdyenPayment\Repositories\Wrapper\StoreRepository;

/**
 * Class SystemInfoService
 *
 * @package AdyenPayment\Components\Integration
 */
class SystemInfoService implements SystemInfoServiceInterface
{
    /**
     * @var ConfigurationService
     */
    private $configuration;

    /**
     * @var StoreRepository
     */
    private $storeRepository;

    /**
     * @param Configuration $configuration
     * @param StoreRepository $repository
     */
    public function __construct(Configuration $configuration, StoreRepository $repository)
    {
        $this->configuration = $configuration;
        $this->storeRepository = $repository;
    }

    /**
     * @inheritDoc
     */
    public function getSystemInfo(): SystemInfo
    {
        return new SystemInfo(
            $this->configuration->getIntegrationVersion(),
            $this->configuration->getPluginVersion() ?? '',
            json_encode($this->storeRepository->getShopTheme()) ?? '',
            Shopware()->Front()->Router()->assemble(['module' => 'frontend',]) ?? '',
            Shopware()->Front()->Router()->assemble(['module' => 'backend']) ?? '',
            $this->configuration->getAsyncProcessUrl('test') ?? '',
            'mysql',
            Shopware()->Db()->getServerVersion() ?? ''
        );
    }
}
