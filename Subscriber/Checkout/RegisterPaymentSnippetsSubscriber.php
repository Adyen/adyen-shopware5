<?php

declare(strict_types=1);

namespace AdyenPayment\Subscriber\Checkout;

use Enlight\Event\SubscriberInterface;
use Shopware_Components_Snippet_Manager;

final class RegisterPaymentSnippetsSubscriber implements SubscriberInterface
{
    /**
     * @var Shopware_Components_Snippet_Manager
     */
    private $snippets;

    public function __construct(Shopware_Components_Snippet_Manager $snippets)
    {
        $this->snippets = $snippets;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'Enlight_Controller_Action_PostDispatch_Frontend_Checkout' => '__invoke',
        ];
    }

    public function __invoke(\Enlight_Controller_ActionEventArgs $args): void
    {
        $subject = $args->getSubject();

        if (!in_array($subject->Request()->getActionName(), ['shippingPayment', 'saveShippingPayment'], true)) {
            return;
        }

        $paymentSnippets = $this->snippets->getNamespace('adyen/checkout/payment');

        $snippets = [
            'updatePaymentInformation' => $paymentSnippets->get(
                'updatePaymentInformation',
                'Update your payment information',
                true
            ),
            'storedPaymentMethodTitle' => $paymentSnippets->get(
                'storedPaymentMethodTitle',
                'Stored payment methods',
                true
            ),
            'paymentMethodTitle' => $paymentSnippets->get(
                'paymentMethodTitle',
                'Payment methods',
                true
            ),
        ];

        $subject->View()->assign('mAdyenSnippets', htmlentities(json_encode($snippets)));
    }
}
