<?php

namespace AdyenPayment\Subscriber;

use Enlight\Event\SubscriberInterface;
use Enlight_Controller_ActionEventArgs;
use Shopware_Controllers_Backend_Order;

class ExtendOrderDetailsHandler implements SubscriberInterface
{
    /**
     * @inheritDoc
     */
    public static function getSubscribedEvents()
    {
        return ['Enlight_Controller_Action_PostDispatchSecure_Backend_Order' => 'onOrderPostDispatch'];
    }

    /**
     * Injects proper extjs files for order view extension.
     *
     * @param Enlight_Controller_ActionEventArgs $args
     */
    public function onOrderPostDispatch(Enlight_Controller_ActionEventArgs $args)
    {
        /** @var Shopware_Controllers_Backend_Order $controller */
        $controller = $args->getSubject();

        $view = $controller->View();
        $request = $controller->Request();

        if ($view && $request->getActionName() === 'index') {
            $view->extendsTemplate('backend/adyen_detail/app.js');
        }

        if ($view && $request->getActionName() === 'load') {
            $view->extendsTemplate('backend/adyen_detail/store/transaction.js');
            $view->extendsTemplate('backend/adyen_detail/model/transaction.js');
            $view->extendsTemplate('backend/adyen_detail/view/window.js');
            $view->extendsTemplate('backend/adyen_list/adyen_order_list.js');
            $view->extendsTemplate('backend/adyen_list/models/adyen_order_model.js');
            $view->extendsTemplate('backend/adyen_detail/controller/adyen_order_details_controller.js');
        }
    }
}
