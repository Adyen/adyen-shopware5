<?php

declare(strict_types=1);

namespace AdyenPayment\Subscriber\Checkout;

use AdyenPayment\Session\ErrorMessageProvider;
use Enlight\Event\SubscriberInterface;

final class AddErrorMessageToViewSubscriber implements SubscriberInterface
{
    /** @var ErrorMessageProvider */
    private $errorMessageProvider;

    public function __construct(ErrorMessageProvider $errorMessageProvider)
    {
        $this->errorMessageProvider = $errorMessageProvider;
    }

    public static function getSubscribedEvents(): array
    {
        return ['Enlight_Controller_Action_PostDispatch_Frontend_Checkout' => ['__invoke', -99999]]; // run as last
    }

    public function __invoke(\Enlight_Controller_ActionEventArgs $args): void
    {
        if (!$this->errorMessageProvider->hasMessages()) {
            return;
        }

        $args->getSubject()->View()->assign('sErrorMessages', $this->errorMessageProvider->read());
    }
}
