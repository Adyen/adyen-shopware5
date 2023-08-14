<?php

namespace AdyenPayment\Components\Integration;

use Adyen\Core\BusinessLogic\Domain\Integration\Version\VersionService as BaseVersionService;
use Adyen\Core\BusinessLogic\Domain\Version\Models\VersionInfo;
use AdyenPayment\Utilities\Plugin;

/**
 * Class VersionService
 *
 * @package AdyenPayment\Components\Integration
 */
class VersionService implements BaseVersionService
{
    /**
     * @inheritDoc
     */
    public function getVersionInfo(): VersionInfo
    {
        $version = Plugin::getVersion();

        return new VersionInfo($version, $version);
    }
}
