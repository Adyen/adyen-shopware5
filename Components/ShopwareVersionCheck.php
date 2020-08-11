<?php declare(strict_types=1);

namespace AdyenPayment\Components;

use OutOfBoundsException;
use PackageVersions\Versions;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ShopwareVersionCheck
{
    const SHOPWARE = '___VERSION___';

    /**
     * @var ContainerInterface
     */
    private $container;
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param ContainerInterface $container
     * @param LoggerInterface $logger
     */
    public function __construct(
        ContainerInterface $container,
        LoggerInterface $logger
    ) {
        $this->container = $container;
        $this->logger = $logger;
    }

    public function isHigherThanShopwareVersion(string $shopwareVersion): bool
    {
        if (!$this->container->has('shopware.release')) {
            return true;
        }

        $version = $this->container->get('shopware.release')->getVersion();

        if ($version === self::SHOPWARE) {
            try {
                list($composerVersion, $sha) = explode('@', Versions::getVersion('shopware/shopware'));
                $version = $composerVersion;
            } catch (OutOfBoundsException $ex) {
                $this->logger->error('OutOfBoundsException', [
					'message' => $ex->getMessage(),
					'file' => $ex->getFile(),
					'line' => $ex->getLine()
				]);
            }
        }

        if ($version[0] !== 'v') {
            $version = 'v' . $version;
        }

        return version_compare($shopwareVersion, $version, '<');
    }
}
