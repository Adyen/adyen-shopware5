<?php

declare(strict_types=1);

namespace AdyenPayment\Subscriber\Backend;

use AdyenPayment\Models\Enum\PaymentMethod\SourceType;
use Enlight\Event\SubscriberInterface;
use Enlight_Controller_Action;
use Enlight_Event_EventArgs;

class RemoveStoredPaymentsSubscriber implements SubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            'Enlight_Controller_Action_PostDispatchSecure_Backend_Payment' => 'providePaymentsWithAdyenHideFunctionality'
        ];
    }

    public function providePaymentsWithAdyenHideFunctionality(Enlight_Event_EventArgs $args)
    {
        /** @var \Shopware_Controllers_Backend_Payment $subject */
        $subject = $args->getSubject();

        if ($subject->Request()->getActionName() !== 'getPayments') {
            return;
        }

        $this->removeHiddenAdyenPaymentMethods($subject);
    }

    private function removeHiddenAdyenPaymentMethods(Enlight_Controller_Action $subject)
    {
        $data = $subject->View()->getAssign('data');

        $adyenSourceType = SourceType::adyen()->getType();
        foreach ($data as $key => $paymentMethod) {
            if ((true === $paymentMethod['hide']
                && $adyenSourceType === $paymentMethod['source'])) {
                unset($data[$key]);
            }
        }

        $subject->View()->assign(
            'data', array_values($data)
        );
    }
}
