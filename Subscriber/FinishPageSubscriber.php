<?php

namespace AdyenPayment\Subscriber;

use Enlight\Event\SubscriberInterface;

/**
 * Class FinishPageSubscriber
 *
 * @package AdyenPayment\Subscriber
 */
class FinishPageSubscriber implements SubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            'Enlight_Controller_Action_PostDispatch_Frontend_Checkout' => '__invoke',
        ];
    }

    public function __invoke(\Enlight_Controller_ActionEventArgs $args): void
    {
        $subject = $args->getSubject();
        if ($args->getRequest()->getActionName() === 'finish') {
            $temporaryId = $args->getRequest()->get('sUniqueID');

            $subject->View()->assign('merchantReference', $temporaryId);

            if (Shopware()->Session()->offsetExists('adyenAction')) {
                $subject->View()->assign('adyenAction', Shopware()->Session()->offsetGet('adyenAction'));
                Shopware()->Session()->offsetUnset('adyenAction');
            }
        }
    }
}
