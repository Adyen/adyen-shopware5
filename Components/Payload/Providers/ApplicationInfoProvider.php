<?php

namespace AdyenPayment\Components\Payload\Providers;

use AdyenPayment\AdyenPayment;
use AdyenPayment\Components\Configuration;
use AdyenPayment\Components\Payload\PaymentContext;
use AdyenPayment\Components\Payload\PaymentPayloadProvider;
use AdyenPayment\Models\Enum\Channel;
use Doctrine\ORM\EntityManagerInterface;
use Shopware\Components\Routing\RouterInterface;
use Shopware\Models\Plugin\Plugin;

/**
 * Class ApplicationInfoProvider
 *
 * @package AdyenPayment\Components
 */
class ApplicationInfoProvider implements PaymentPayloadProvider
{
    /**
     * @var EntityManagerInterface
     */
    private $modelManager;
    /**
     * @var Configuration
     */
    private $configuration;
    /**
     * @var RouterInterface
     */
    private $router;

    public function __construct(
        RouterInterface $router,
        EntityManagerInterface $modelManager,
        Configuration $configuration
    ) {
        $this->router = $router;
        $this->modelManager = $modelManager;
        $this->configuration = $configuration;
    }

    /**
     * @param PaymentContext $context
     *
     * @return array
     */
    public function provide(PaymentContext $context): array
    {
        $returnUrl = $this->router->assemble([
                'controller' => 'transparent',
                'action' => 'redirect',
            ]).'?'.http_build_query([
                'merchantReference' => $context->getOrder()->getNumber(),
            ]);
        $plugin = $this->modelManager->getRepository(Plugin::class)->findOneBy(['name' => AdyenPayment::NAME]);

        return [
            'additionalData' => [
                'executeThreeD' => true,
                'allow3DS2' => true,
            ],
            'channel' => Channel::WEB,
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
