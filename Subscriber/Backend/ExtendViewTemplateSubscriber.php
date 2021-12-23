<?php

declare(strict_types=1);

namespace AdyenPayment\Subscriber\Backend;

use Enlight\Event\SubscriberInterface;

final class ExtendViewTemplateSubscriber implements SubscriberInterface
{
    /**
     * @psalm-return array{Enlight_Controller_Action_PostDispatchSecure_Backend_Order: '__invoke'}
     */
    public static function getSubscribedEvents(): array
    {
        return [
            'Enlight_Controller_Action_PostDispatchSecure_Backend_Order' => '__invoke',
        ];
    }

    public function __invoke(\Enlight_Event_EventArgs $args): void
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
