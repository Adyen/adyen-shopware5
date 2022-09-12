<?php

declare(strict_types=1);

namespace AdyenPayment\Subscriber\Backend;

use AdyenPayment\Dbal\Remover\PaymentMeanSubShopRemoverInterface;
use Enlight\Event\SubscriberInterface;
use Symfony\Component\HttpFoundation\Response;

final class RemoveSubShopPaymentMethodSubscriber implements SubscriberInterface
{
    public const DELETE_VALUES_ACTION = 'deleteValues';

    /** @var PaymentMeanSubShopRemoverInterface */
    private $paymentMeanSubShopRemover;

    public function __construct(PaymentMeanSubShopRemoverInterface $paymentMeanSubShopRemover)
    {
        $this->paymentMeanSubShopRemover = $paymentMeanSubShopRemover;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'Enlight_Controller_Action_PostDispatch_Backend_Config' => '__invoke',
        ];
    }

    public function __invoke(\Enlight_Event_EventArgs $args): void
    {
        $request = $args->get('request') ?? false;
        $response = $args->get('response') ?? false;

        if (!$request || !$response) {
            return;
        }

        if (!$this->isSubShopDeleted($request->getParam('id'), $response, $request->getActionName())) {
            return;
        }

        $this->paymentMeanSubShopRemover->removeBySubShopId($request->getParam('id'));
    }

    private function isSubShopDeleted($id, $response, string $action): bool
    {
        return null !== $id
            && Response::HTTP_OK === $response->getHttpResponseCode()
            && self::DELETE_VALUES_ACTION === $action;
    }
}
