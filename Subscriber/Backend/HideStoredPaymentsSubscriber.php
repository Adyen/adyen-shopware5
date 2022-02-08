<?php

declare(strict_types=1);

namespace AdyenPayment\Subscriber\Backend;

use AdyenPayment\Collection\Payment\PaymentMeanCollection;
use Enlight\Event\SubscriberInterface;
use Symfony\Component\HttpFoundation\Response;

final class HideStoredPaymentsSubscriber implements SubscriberInterface
{
    private const GET_PAYMENTS_ACTION = 'getPayments';

    public static function getSubscribedEvents(): array
    {
        return [
            'Enlight_Controller_Action_PostDispatchSecure_Backend_Payment' => '__invoke',
            'Enlight_Controller_Action_PostDispatchSecure_Backend_Shipping' => '__invoke',
        ];
    }

    public function __invoke(\Enlight_Controller_ActionEventArgs $args): void
    {
        if (!$this->isSuccessGetPaymentAction($args)) {
            return;
        }

        $data = $args->getSubject()->View()->getAssign('data') ?? [];
        if (!count($data)) {
            return;
        }

        $data = PaymentMeanCollection::createFromShopwareArray($data)
            ->filterExcludeHidden()
            ->toShopwareArray();

        $args->getSubject()->View()->assign('data', array_values($data));
    }

    private function isSuccessGetPaymentAction(\Enlight_Controller_ActionEventArgs $args): bool
    {
        if (!$args->getResponse() || Response::HTTP_OK !== $args->getResponse()->getHttpResponseCode()) {
            return false;
        }

        if (!$args->getRequest() || self::GET_PAYMENTS_ACTION !== $args->getRequest()->getActionName()) {
            return false;
        }

        return true;
    }
}
