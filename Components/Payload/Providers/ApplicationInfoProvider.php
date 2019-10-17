<?php
declare(strict_types=1);

namespace MeteorAdyen\Models\Payload\Providers;

use MeteorAdyen\Models\ShopwareInfo;

/**
 * Class ApplicationInfoProvider
 * @package MeteorAdyen\Models
 */
class ApplicationInfoProvider implements PaymentPayloadProvider
{
    private $info;

    /**
     * ApplicationInfoProvider constructor.
     * @param $info
     */
    public function __construct(ShopwareInfo $info)
    {
        $this->info = $info;
    }

    /**
     * @param PayContext $context
     * @return array
     */
    public function provide(PayContext $context): array
    {
        return [
            'applicationInfo' => [
                "adyenPaymentSource" => [
                    "name" => $this->info->getPluginName(),
                    "version" => $this->info->getPluginVersion(),
                ],
                'externalPlatform' => [
                    'name' => $this->info->getShopwareName(),
                    'version' => $this->info->getPluginVersion(),
                    'integrator' => $this->info->getIntegrator(),
                ],
            ],
        ];
    }
}