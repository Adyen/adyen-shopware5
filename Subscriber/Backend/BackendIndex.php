<?php

namespace AdyenPayment\Subscriber\Backend;

use Enlight\Event\SubscriberInterface;

class BackendIndex implements SubscriberInterface
{
    public static function getSubscribedEvents()
    {
        return [
            'Enlight_Controller_Action_PostDispatch_Backend_Index' => 'onPostDispatchBackendIndex'
        ];
    }

    /**
     * @param \Enlight_Controller_ActionEventArgs $args
     */
    public function onPostDispatchBackendIndex($args)
    {
        $action = $args->getSubject();
        $request = $action->Request();
        $response = $action->Response();
        $view = $action->View();

        if ($request->getActionName() === 'load') {
            $view->extendsTemplate('backend/_resources/js/AdyenShopNotifications.js');
        }

        if (!$request->isDispatched()
            || $response->isException()
            || $request->getActionName() !== 'index'
            || !$view->hasTemplate()
        ) {
            return;
        }

        $view->extendsTemplate('backend/index/adyen_header.tpl');
    }
}
