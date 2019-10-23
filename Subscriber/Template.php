<?php

declare(strict_types=1);

namespace MeteorAdyen\Subscriber;

use Enlight\Event\SubscriberInterface;
use Enlight_Template_Manager;

/**
 * Class Template
 * @package MeteorAdyen\Subscriber
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
     * @param Enlight_Template_Manager $templateManager
     */
    public function __construct($pluginDirectory, Enlight_Template_Manager $templateManager)
    {
        $this->templateManager = $templateManager;
        $this->pluginDirectory = $pluginDirectory;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            'Enlight_Controller_Action_PreDispatch' => 'onPreDispatch',
            'Enlight_Controller_Action_PostDispatchSecure_Backend_Order' => 'onBackendOrderPostDispatch',
        ];
    }

    public function onPreDispatch()
    {
        $this->templateManager->addTemplateDir($this->pluginDirectory . '/Resources/views');
    }

    public function onBackendOrderPostDispatch(\Enlight_Event_EventArgs $args)
    {
        /** @var \Shopware_Controllers_Backend_Order $controller */
        $controller = $args->getSubject();

        $view = $controller->View();
        $request = $controller->Request();

        if ($request->getActionName() == 'index') {
            $view->extendsTemplate('backend/meteor_adyen_order/app.js');
        }

        if ($request->getActionName() == 'load') {
            $view->extendsTemplate('backend/meteor_adyen_order/view/detail/window.js');
            $view->extendsTemplate('backend/meteor_adyen_order/model/order.js');
        }
    }
}
