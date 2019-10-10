<?php
declare(strict_types=1);

namespace MeteorAdyen\Models\Providers;

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
    public function __construct($info)
    {
        $this->info = $info;
    }

    /**
     * @param PayContext $context
     * @return array
     */
    public function provide(PayContext $context): array
    {
        // TODO: Implement provide() method.
        return [
            'applicationInfo' => [
                'externalPlatform' => [
                    'version' => $this->info->getShopwareVersion(),
                ],
            ],
        ];
    }
}