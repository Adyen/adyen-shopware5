<?php

declare(strict_types=1);

namespace AdyenPayment\Subscriber;

use Enlight\Event\SubscriberInterface;
use Enlight_Components_Session_Namespace;

final class AssignStoredPaymentMethodToSession implements SubscriberInterface
{
    /** @var Enlight_Components_Session_Namespace */
    private $session;

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
        $actionName = $args->getRequest()->getActionName();

        $isShippingPaymentUpdate = 'shippingPayment' === $actionName && $args->getRequest()->getParam('isXHR');
        $isSaveShippingPayment = 'saveShippingPayment' === $actionName;
        if (!$isShippingPaymentUpdate && !$isSaveShippingPayment) {
            return;
        }

        $storedMethodId = $args->getRequest()->getParam('adyenStoredPaymentMethodId');
        $this->session->offsetSet('adyenStoredPaymentMethodId', $storedMethodId);
        if ($storedMethodId) {
            Shopware()->Modules()->Admin()->sUpdatePayment(
                str_replace("_$storedMethodId", '', $args->getRequest()->getPost('payment'))
            );
        }
    }
}
