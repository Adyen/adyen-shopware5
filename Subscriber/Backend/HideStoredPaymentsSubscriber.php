<?php

declare(strict_types=1);

namespace AdyenPayment\Subscriber\Backend;

use AdyenPayment\Utilities\Plugin;
use Enlight\Event\SubscriberInterface;
use Symfony\Component\HttpFoundation\Response;

final class HideStoredPaymentsSubscriber implements SubscriberInterface
{
    private const GET_PAYMENTS_ACTION = 'getPayments';

    public static function getSubscribedEvents(): array
    {
        return [
            'Enlight_Controller_Action_PostDispatchSecure_Backend_Payment' => '__invoke',
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

        $args->getSubject()->View()->assign('data', $this->filterHiddenPaymentMeans($data));
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

    private function filterHiddenPaymentMeans(array $paymentMeans): array
    {
        return array_values(array_filter(
            array_map(static function (array $paymentMean) {
                if (!Plugin::isAdyenPaymentMean($paymentMean['name'])) {
                    return $paymentMean;
                }

                return !$paymentMean['hide'] ? $paymentMean : null;
            }, $paymentMeans)
        ));
    }
}
