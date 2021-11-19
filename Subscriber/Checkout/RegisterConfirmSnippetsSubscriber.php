<?php

declare(strict_types=1);

namespace AdyenPayment\Subscriber\Checkout;

use Enlight\Event\SubscriberInterface;
use Shopware_Components_Snippet_Manager;

final class RegisterConfirmSnippetsSubscriber implements SubscriberInterface
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

        if ('confirm' !== $subject->Request()->getActionName()) {
            return;
        }

        $errorSnippets = $this->snippets->getNamespace('adyen/checkout/error');

        $snippets = [];
        $snippets['errorTransactionCancelled'] = $errorSnippets->get(
            'errorTransactionCancelled',
            'Your transaction was cancelled by the Payment Service Provider.',
            true
        );
        $snippets['errorTransactionProcessing'] = $errorSnippets->get(
            'errorTransactionProcessing',
            'An error occured while processing your payment.',
            true
        );
        $snippets['errorTransactionRefused'] = $errorSnippets->get(
            'errorTransactionRefused',
            'Your transaction was refused by the Payment Service Provider.',
            true
        );
        $snippets['errorTransactionUnknown'] = $errorSnippets->get(
            'errorTransactionUnknown',
            'Your transaction was cancelled due to an unknown reason.',
            true
        );
        $snippets['errorTransactionNoSession'] = $errorSnippets->get(
            'errorTransactionNoSession',
            'Your transaction was cancelled due to an unknown reason. Please make sure your browser allows cookies.',
            true
        );

        $subject->View()->assign('mAdyenSnippets', htmlentities(json_encode($snippets)));
    }
}
