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
            $subject->View()->assign('adyenCountryCode', $this->resolveCountryCode());

            if (Shopware()->Session()->offsetExists('adyenAction')) {
                $subject->View()->assign('adyenAction', Shopware()->Session()->offsetGet('adyenAction'));
                Shopware()->Session()->offsetUnset('adyenAction');
            }
        }
    }

    /**
     * Resolves the billing country ISO code (alpha-2) for the current user, used as a fallback
     * countryCode for the Adyen Giving (donations) checkout configuration.
     *
     * @return string
     */
    private function resolveCountryCode(): string
    {
        if (
            Shopware()->Modules() &&
            ($sAdmin = Shopware()->Modules()->Admin()) &&
            ($userData = $sAdmin->sGetUserData()) &&
            isset($userData['additional']['country']['countryiso'])
        ) {
            return (string)$userData['additional']['country']['countryiso'];
        }

        return '';
    }
}
