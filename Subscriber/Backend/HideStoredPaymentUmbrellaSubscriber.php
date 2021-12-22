<?php

declare(strict_types=1);

namespace AdyenPayment\Subscriber\Backend;

use AdyenPayment\AdyenPayment;
use Enlight\Event\SubscriberInterface;
use Symfony\Component\HttpFoundation\Response;

final class HideStoredPaymentUmbrellaSubscriber implements SubscriberInterface
{
    private const GET_PAYMENTS_ACTION = 'getPayments';

    public static function getSubscribedEvents(): array
    {
        return [
            'Enlight_Controller_Action_PostDispatchSecure' => '__invoke',
        ];
    }

    public function __invoke(\Enlight_Controller_ActionEventArgs $args): void
    {
        $request = $args->get('request') ?? false;
        $response = $args->get('response') ?? false;

        if (!$request || !$response) {
            return;
        }

        if (
            Response::HTTP_OK !== $response->getHttpResponseCode()
            || self::GET_PAYMENTS_ACTION !== $request->getActionName()
        ) {
            return;
        }

        $assign = $args->getSubject()->View()->getAssign();
        $data = $assign['data'] ?? null;
        if (null === $data) {
            return;
        }

        $filtered = array_values(array_filter($assign['data'], static function($paymentMethod) {
            return AdyenPayment::ADYEN_STORED_PAYMENT_UMBRELLA_CODE !== $paymentMethod['name'];
        }));

        $args->getSubject()->View()->assign(['success' => true, 'data' => $filtered]);
    }
}
