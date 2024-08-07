<?php

declare(strict_types=1);

namespace AdyenPayment\Subscriber;

use Enlight\Event\SubscriberInterface;
use Enlight_Components_Session_Namespace;
use Enlight_Event_EventArgs;
use Shopware\Models\Customer\Customer;

/**
 * Class AddExpressCheckoutToView
 *
 * @package AdyenPayment\Subscriber
 */
final class AddExpressCheckoutToView implements SubscriberInterface
{
    /**
     * @var Enlight_Components_Session_Namespace
     */
    private $session;

    public function __construct(Enlight_Components_Session_Namespace $session) {
        $this->session = $session;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'Enlight_Controller_Action_PostDispatchSecure_Frontend_Detail' => 'handleProductDetailsPage',
            'Enlight_Controller_Action_PostDispatchSecure_Frontend_Checkout' => 'handleCartPage',
        ];
    }

    public function handleProductDetailsPage(Enlight_Event_EventArgs $args): void
    {
        if ($args->getRequest()->getActionName() !== 'index') {
            return;
        }

        $args->getSubject()->View()->assign(
            'adyenShowExpressCheckout', true
        );
    }

    public function handleCartPage(Enlight_Event_EventArgs $args): void
    {
        if ($args->getRequest()->getActionName() !== 'cart') {
            return;
        }

        $args->getSubject()->View()->assign(
            'adyenShowExpressCheckout', true
        );
    }

    private function isUserLoggedIn(): bool
    {
        if (!(bool)$this->session->get('sUserId')) {
            return false;
        }

        $userData = Shopware()->Modules()->Admin()->sGetUserData();
        if (
            !empty($userData['additional']['user']['accountmode']) &&
            (int)$userData['additional']['user']['accountmode'] === Customer::ACCOUNT_MODE_FAST_LOGIN
        ) {
            return false;
        }

        return true;
    }
}
