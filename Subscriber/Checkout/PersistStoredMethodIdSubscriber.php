<?php

declare(strict_types=1);

namespace AdyenPayment\Subscriber\Checkout;

use AdyenPayment\AdyenPayment;
use Enlight\Event\SubscriberInterface;
use Enlight_Components_Session_Namespace;

final class PersistStoredMethodIdSubscriber implements SubscriberInterface
{
    private Enlight_Components_Session_Namespace $session;

    public function __construct(Enlight_Components_Session_Namespace $session)
    {
        $this->session = $session;
    }

    public static function getSubscribedEvents(): array
    {
        return ['Enlight_Controller_Action_PostDispatch_Frontend_Checkout' => '__invoke'];
    }

    public function __invoke(\Enlight_Controller_ActionEventArgs $args): void
    {
        $subject = $args->getSubject();
        $actionName = $args->getRequest()->getActionName();

        $isShippingPaymentUpdate = 'shippingPayment' === $actionName && $args->getRequest()->getParam('isXHR');
        $isSaveShippingPayment = 'saveShippingPayment' === $actionName;
        if (!$isShippingPaymentUpdate && !$isSaveShippingPayment) {
            return;
        }

        $storedMethodId = $args->getRequest()->getParam(AdyenPayment::SESSION_ADYEN_STORED_METHOD_ID);
        $this->session->set(AdyenPayment::SESSION_ADYEN_STORED_METHOD_ID, $storedMethodId);
    }
}
