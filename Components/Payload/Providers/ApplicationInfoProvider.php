<?php

namespace AdyenPayment\Components\Payload\Providers;

use AdyenPayment\Components\Configuration;
use AdyenPayment\Components\Payload\PaymentContext;
use AdyenPayment\Components\Payload\PaymentPayloadProvider;
use AdyenPayment\AdyenPayment;
use Shopware\Components\Model\ModelManager;
use Shopware\Models\Plugin\Plugin;
use AdyenPayment\Models\Enum\Channel;

/**
 * Class ApplicationInfoProvider
 * @package AdyenPayment\Components
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

        $plugin = $this->modelManager->getRepository(Plugin::class)->findOneBy(['name' => AdyenPayment::NAME]);

        return [
            'additionalData' => [
                'executeThreeD' => true,
                'allow3DS2' => true,
            ],
            'channel' => Channel::WEB,
            'origin' => $context->getOrigin(),
            'redirectFromIssuerMethod' => 'GET',
            'redirectToIssuerMethod' => 'POST',
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
