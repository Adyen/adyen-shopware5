<?php

namespace AdyenPayment\Subscriber;

use Enlight\Event\SubscriberInterface;

class ControllerPath implements SubscriberInterface
{
    /**
     * @var string
     */
    private $pluginDirectory;

    /**
     * @param $pluginDirectory
     */
    public function __construct($pluginDirectory)
    {
        $this->pluginDirectory = $pluginDirectory;
    }

    public static function getSubscribedEvents()
    {
        return [
            'Enlight_Controller_Dispatcher_ControllerPath_Backend_AdyenPaymentMain' => 'onGetControllerPromotion',
            'Enlight_Controller_Dispatcher_ControllerPath_Backend_AdyenAuthorization' => 'onGetControllerPromotion',
            'Enlight_Controller_Dispatcher_ControllerPath_Frontend_AdyenAsyncProcess' => 'onGetControllerPromotion',
            'Enlight_Controller_Dispatcher_ControllerPath_Frontend_AdyenWebhook' => 'onGetControllerPromotion',
            'Enlight_Controller_Dispatcher_ControllerPath_Frontend_AdyenTest' => 'onGetControllerPromotion',
            'Enlight_Controller_Dispatcher_ControllerPath_Frontend_AdyenPaymentProcess' => 'onGetControllerPromotion',
            'Enlight_Controller_Dispatcher_ControllerPath_Frontend_AdyenDonations' => 'onGetControllerPromotion',
            'Shopware_Controllers_Frontend_AdyenExpressCheckout' => 'onGetControllerPromotion',
        ];
    }


    /**
     * Controller path handler, generates controller path based on a event name
     *
     * @param \Enlight_Event_EventArgs $arguments
     * @return  string Controller path
     */
    public function onGetControllerPromotion(\Enlight_Event_EventArgs $arguments): string
    {
        $eventName = $arguments->getName();

        $moduleAndController = str_replace('Enlight_Controller_Dispatcher_ControllerPath_', '', $eventName);
        list($module, $controller) = explode('_', $moduleAndController);

        return "{$this->pluginDirectory}/Controllers/{$module}/{$controller}.php";
    }
}
