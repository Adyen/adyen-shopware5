<?php

declare(strict_types=1);

namespace AdyenPayment\Subscriber;

use Enlight\Event\SubscriberInterface;
use Enlight_Components_Session_Namespace;

final class UpdateStoredPaymentMethodViewData implements SubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return ['Enlight_Controller_Action_PostDispatch_Frontend_Checkout' => '__invoke'];
    }

    public function __invoke(\Enlight_Controller_ActionEventArgs $args): void
    {
        $subject = $args->getSubject();
        $actionName = $args->getRequest()->getActionName();
        $isShippingPaymentView = 'shippingPayment' === $actionName && !$args->getRequest()->getParam('isXHR');
        if (!$isShippingPaymentView) {
            return;
        }

        $userData = $subject->View()->getAssign('sUserData');
        $storedMethodId = $userData['additional']['payment']['storedPaymentMethodId'] ?? null;
        if (empty($storedMethodId)) {
            return;
        }

        // Make sure that form data has complete id in case when stored payment method is selected
        $formData = $subject->View()->getAssign('sFormData');
        $formData['payment'] .= "_$storedMethodId";
        $subject->View()->assign('sFormData', $formData);
    }
}
