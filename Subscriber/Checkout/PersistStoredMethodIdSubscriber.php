<?php

declare(strict_types=1);

namespace AdyenPayment\Subscriber\Checkout;

use AdyenPayment\AdyenPayment;
use Enlight\Event\SubscriberInterface;
use Enlight_Components_Session_Namespace;
use Shopware_Components_Modules;

final class PersistStoredMethodIdSubscriber implements SubscriberInterface
{
    /** @var Enlight_Components_Session_Namespace */
    private $session;

    /** @var Shopware_Components_Modules */
    private $modules;

    public function __construct(Enlight_Components_Session_Namespace $session, Shopware_Components_Modules $modules)
    {
        $this->session = $session;
        $this->modules = $modules;
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

        $storedMethodId = $args->getRequest()->getParam(AdyenPayment::SESSION_ADYEN_STORED_METHOD_ID);
        $this->session->offsetSet(AdyenPayment::SESSION_ADYEN_STORED_METHOD_ID, $storedMethodId);
        if ($storedMethodId) {
            $this->modules->Admin()->sUpdatePayment(
                str_replace("_${storedMethodId}", '', $args->getRequest()->getPost('payment'))
            );
        }
    }
}
