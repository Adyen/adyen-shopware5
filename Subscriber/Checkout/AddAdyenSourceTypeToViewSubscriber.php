<?php

declare(strict_types=1);

namespace AdyenPayment\Subscriber\Checkout;

use AdyenPayment\Models\Enum\PaymentMethod\SourceType;
use Enlight\Event\SubscriberInterface;
use Enlight_Event_EventArgs;

final class AddAdyenSourceTypeToViewSubscriber implements SubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            'Enlight_Controller_Action_PostDispatch_Frontend_Checkout' => '__invoke',
        ];
    }

    public function __invoke(Enlight_Event_EventArgs $args)
    {
        $subject = $args->getSubject();

        if ('confirm' !== $subject->Request()->getActionName()) {
            return;
        }

        $subject->View()->assign('adyenSourceType', SourceType::adyen()->getType());
    }
}
