<?php

declare(strict_types=1);

namespace AdyenPayment\Subscriber;

use Enlight\Event\SubscriberInterface;
use Enlight_Template_Manager;

/**
 * Class Template.
 */
class Template implements SubscriberInterface
{
    /**
     * @var Enlight_Template_Manager
     */
    private $templateManager;

    /**
     * @var string
     */
    private $pluginDirectory;

    /**
     * @param string $pluginDirectory
     */
    public function __construct($pluginDirectory, Enlight_Template_Manager $templateManager)
    {
        $this->templateManager = $templateManager;
        $this->pluginDirectory = $pluginDirectory;
    }

    /**
     * {@inheritdoc}
     *
     * @return string[]
     *
     * @psalm-return array{Enlight_Controller_Action_PreDispatch: 'onPreDispatch', Enlight_Controller_Action_PostDispatchSecure_Backend_Order: 'onBackendOrderPostDispatch'}
     */
    public static function getSubscribedEvents()
    {
        return [
            'Enlight_Controller_Action_PreDispatch' => 'onPreDispatch',
            'Enlight_Controller_Action_PostDispatchSecure_Backend_Order' => 'onBackendOrderPostDispatch',
        ];
    }

    public function onPreDispatch(): void
    {
        $this->templateManager->addTemplateDir($this->pluginDirectory.'/Resources/views');
    }

    public function onBackendOrderPostDispatch(\Enlight_Event_EventArgs $args): void
    {
        /** @var \Shopware_Controllers_Backend_Order $controller */
        $controller = $args->getSubject();

        $view = $controller->View();
        $request = $controller->Request();

        if ('index' === $request->getActionName()) {
            $view->extendsTemplate('backend/adyen_payment_order/app.js');
        }

        if ('load' === $request->getActionName()) {
            $view->extendsTemplate('backend/adyen_payment_order/view/detail/window.js');
            $view->extendsTemplate('backend/adyen_payment_order/model/order.js');
        }
    }
}
