<?php

declare(strict_types=1);

namespace AdyenPayment\Components\Payload\Providers;

use AdyenPayment\AdyenPayment;
use AdyenPayment\Components\Configuration;
use AdyenPayment\Components\Payload\PaymentContext;
use AdyenPayment\Components\Payload\PaymentPayloadProvider;
use AdyenPayment\Components\ShopwareVersionCheck;
use AdyenPayment\Models\Enum\Channel;
use Doctrine\ORM\EntityManagerInterface;
use Shopware\Components\Routing\RouterInterface;
use Shopware\Models\Plugin\Plugin;

/**
 * Class ApplicationInfoProvider.
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

    /**
     * @var ShopwareVersionCheck
     */
    private $shopwareVersionCheck;

    public function __construct(
        RouterInterface $router,
        EntityManagerInterface $modelManager,
        Configuration $configuration,
        ShopwareVersionCheck $shopwareVersionCheck
    ) {
        $this->router = $router;
        $this->modelManager = $modelManager;
        $this->configuration = $configuration;
        $this->shopwareVersionCheck = $shopwareVersionCheck;
    }

    public function provide(PaymentContext $context): array
    {
        $returnUrl = $this->router->assemble([
            'controller' => 'process',
            'action' => 'return',
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
                    'version' => $this->shopwareVersionCheck->getShopwareVersion(),
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
