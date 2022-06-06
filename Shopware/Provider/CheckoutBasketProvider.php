<?php

declare(strict_types=1);

namespace AdyenPayment\Shopware\Provider;

use Shopware\Components\DependencyInjection\Container;

final class CheckoutBasketProvider extends \Shopware_Controllers_Frontend_Checkout implements CheckoutBasketProviderInterface
{
    public function __construct(
        Container $container,
        \Enlight_Template_Manager $engine
    ) {
        $this->container = $container;
        $this->view = new \Enlight_View_Default($engine);
        $this->init();

        parent::__construct();
    }

    public function __invoke($mergeProportional = true): array
    {
        // Initialize front controller to mitigate getBasket expectations
        $this->Front();

        return $this->getBasket($mergeProportional);
    }
}
