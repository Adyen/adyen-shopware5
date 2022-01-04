<?php

declare(strict_types=1);

namespace AdyenPayment\Subscriber\Backend;

use AdyenPayment\Models\Enum\PaymentMethod\SourceType;
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

        $data = $args->getSubject()->View()->getAssign()['data'] ?? [];
        if (0 === count($data)) {
            return;
        }

        $adyenSourceType = SourceType::adyen();

        $data = array_values(array_filter($data, static function($paymentMethod) use ($adyenSourceType) {
            if (false === (bool) ($paymentMethod['hide'] ?? false)) {
                return true;
            }

            $sourceType = SourceType::load($paymentMethod['source'] ?? null);
            if (!$sourceType->equals($adyenSourceType)) {
                return true;
            }

            return false;
        }));

        $args->getSubject()->View()->assign('data', $data);
    }
}
