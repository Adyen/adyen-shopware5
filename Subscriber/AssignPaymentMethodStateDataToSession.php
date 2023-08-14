<?php

declare(strict_types=1);

namespace AdyenPayment\Subscriber;

use Enlight\Event\SubscriberInterface;

/**
 * Class AddErrorMessageToView
 *
 * When pay button is clicked on the checkout payment method state data collected from Adyen Web components is submitted
 * with the rest of the order data but Shopware redirects to the @see \Shopware_Controllers_Frontend_AdyenPaymentProcess
 * controller without submitted data. Because of this, we need to transfer payment state data via session to the
 * @see \Shopware_Controllers_Frontend_AdyenPaymentProcess controller.
 *
 * @package AdyenPayment\Subscriber
 */
final class AssignPaymentMethodStateDataToSession implements SubscriberInterface
{
    /** @var \Enlight_Components_Session_Namespace */
    private $session;

    public function __construct(\Enlight_Components_Session_Namespace $session)
    {
        $this->session = $session;
    }

    public static function getSubscribedEvents(): array
    {
        return ['Enlight_Controller_Action_PostDispatch_Frontend_Checkout' => '__invoke'];
    }

    public function __invoke(\Enlight_Controller_ActionEventArgs $args): void
    {
        if ('payment' !== $args->getRequest()->getActionName()) {
            return;
        }

        $this->session->offsetSet(
            'adyenPaymentMethodStateData',
            $args->getRequest()->get('adyenPaymentMethodStateData')
        );
        $this->session->offsetSet(
            'adyenIsXHR',
            $args->getRequest()->getParam('isXHR')
        );
    }
}
