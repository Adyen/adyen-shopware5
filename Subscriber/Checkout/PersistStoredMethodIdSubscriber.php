<?php

declare(strict_types=1);

namespace AdyenPayment\Subscriber\Checkout;

use Enlight\Event\SubscriberInterface;
use Enlight_Components_Session_Namespace;

final class PersistStoredMethodIdSubscriber implements SubscriberInterface
{
    private Enlight_Components_Session_Namespace $session;

    public function __construct(
        Enlight_Components_Session_Namespace $session
    ) {
        $this->session = $session;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'Enlight_Controller_Action_PostDispatch_Frontend_Checkout' => '__invoke',
        ];
    }

    public function __invoke(\Enlight_Controller_ActionEventArgs $args): void
    {
        $subject = $args->getSubject();
        $actionName = $subject->Request()->getActionName();
        $isShippingPaymentUpdate = 'shippingPayment' === $actionName && $subject->Request()->getParam('isXHR');
        $isSaveShippingPayment = 'saveShippingPayment' === $actionName;
        $storedMethodId = $subject->Request()->getParam('storedMethodId');
        if (null !== $storedMethodId && ($isShippingPaymentUpdate || $isSaveShippingPayment)) {
            $this->session->set('storedMethodId', $storedMethodId);
        }
    }
}
