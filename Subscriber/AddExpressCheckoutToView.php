<?php

declare(strict_types=1);

namespace AdyenPayment\Subscriber;

use Adyen\Core\Infrastructure\ServiceRegister;
use AdyenPayment\Services\CustomerService;
use Enlight\Event\SubscriberInterface;
use Enlight_Components_Session_Namespace;
use Enlight_Event_EventArgs;

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

        $this->assignExpressCheckoutViewData($args);
    }

    public function handleCartPage(Enlight_Event_EventArgs $args): void
    {
        if ($args->getRequest()->getActionName() !== 'cart') {
            return;
        }

        $this->assignExpressCheckoutViewData($args);
    }

    /**
     * Shopware's own $userLoggedIn view variable is true for fast-login (guest) sessions as well, while the
     * plugin treats those sessions as guests (see CustomerService::isUserLoggedIn()). Expose a plugin-owned flag
     * so that the express checkout components request address and email in the same cases the server expects them.
     */
    private function assignExpressCheckoutViewData(Enlight_Event_EventArgs $args): void
    {
        /** @var CustomerService $customerService */
        $customerService = ServiceRegister::getService(CustomerService::class);

        $args->getSubject()->View()->assign('adyenShowExpressCheckout', true);
        $args->getSubject()->View()->assign('adyenUserLoggedIn', $customerService->isUserLoggedIn());
    }
}
