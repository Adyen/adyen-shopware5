<?php

namespace MeteorAdyen\Components\Payload\Providers;

use MeteorAdyen\Components\Configuration;
use MeteorAdyen\Components\Payload\PaymentContext;
use MeteorAdyen\Components\Payload\PaymentPayloadProvider;
use MeteorAdyen\MeteorAdyen;
use Shopware\Components\Model\ModelManager;
use Shopware\Models\Plugin\Plugin;

/**
 * Class ApplicationInfoProvider
 * @package MeteorAdyen\Components
 */
class ApplicationInfoProvider implements PaymentPayloadProvider
{
    /**
     * @var ModelManager
     */
    private $modelManager;
    /**
     * @var Configuration
     */
    private $configuration;

    public function __construct(Configuration $configuration)
    {
        $this->configuration = $configuration;
        $this->modelManager = Shopware()->Container()->get('models');
    }

    /**
     * @param PaymentContext $context
     * @return array
     */
    public function provide(PaymentContext $context): array
    {
        $returnUrl = Shopware()->Router()->assemble([
            'controller' => 'process',
            'action' => 'return',
        ]);

        $plugin = $this->modelManager->getRepository(Plugin::class)->findOneBy(array('name' => MeteorAdyen::NAME));

        return [
            'additionalData' => [
                'executeThreeD' => true,
                'allow3DS2' => true,
            ],
            "channel" => "Web",
            'origin' => $context->getOrigin(),
            'returnUrl' => $returnUrl,
            'merchantAccount' => $this->configuration->getMerchantAccount(),
            'applicationInfo' => [
                'adyenPaymentSource' => [
                    'name' => $plugin->getLabel(),
                    'version' => $plugin->getVersion(),
                ],
                'externalPlatform' => [
                    'name' => 'Shopware',
                    'version' => '5.6',
                    'integrator' => $plugin->getAuthor(),
                ],
                'merchantApplication' => [
                    'name' => $plugin->getLabel(),
                    'version' => $plugin->getVersion(),
                ],
            ],
        ];
    }
}
