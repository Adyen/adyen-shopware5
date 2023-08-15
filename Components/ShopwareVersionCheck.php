<?php

declare(strict_types=1);

namespace AdyenPayment\Components;

use OutOfBoundsException;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ShopwareVersionCheck
{
    public const SHOPWARE = '___VERSION___';

    /** @var ContainerInterface */
    private $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function isHigherThanShopwareVersion(string $shopwareVersion): bool
    {
        if (!$this->container->has('shopware.release')) {
            return true;
        }

        $version = $this->getShopwareVersion();

        if ('v' !== $version[0]) {
            $version = 'v'.$version;
        }

        return version_compare($shopwareVersion, $version, '<');
    }

    public function getShopwareVersion(): string
    {
        $version = $this->container->get('shopware.release')->getVersion();

        if (self::SHOPWARE === $version && class_exists('\PackageVersions\Versions')) {
            try {
                [$composerVersion, $sha] = explode('@', \PackageVersions\Versions::getVersion('shopware/shopware'));
                $version = $composerVersion;
            } catch (OutOfBoundsException $ex) {
                /* Intentionally left empty */
            }
        }

        return $version;
    }
}
