<?php

declare(strict_types=1);

namespace AdyenPayment\Subscriber;

use AdyenPayment\Dbal\Remover\Payment\PaymentMeansSubshopsRemoverInterface;
use Enlight\Event\SubscriberInterface;
use Symfony\Component\HttpFoundation\Response;

final class RemoveSubShopPaymentMethodsSubscriber implements SubscriberInterface
{
    const DELETE_VALUES_ACTION = 'deleteValues';

    /**
     * @var PaymentMeansSubshopsRemoverInterface
     */
    private $paymentMeansSubshopsRemover;

    public function __construct(
        PaymentMeansSubshopsRemoverInterface $paymentMeansSubshopsRemover
    )
    {
        $this->paymentMeansSubshopsRemover = $paymentMeansSubshopsRemover;
    }

    public static function getSubscribedEvents()
    {
        return [
            'Enlight_Controller_Action_PostDispatch_Backend_Config' => '__invoke',
        ];
    }

    public function __invoke(\Enlight_Event_EventArgs $args)
    {
        if (!$this->isSubShopDeleted(
            $args->get('request')->getParam('id'),
            $args->get('response'),
            $args->get('request')->getActionName())
        ) {
            return;
        }

        $this->paymentMeansSubshopsRemover->deleteAllAdyenPaymentMethodsForSubshop($args->get('request')->getParam('id'));
    }

    private function isSubShopDeleted($id, $response, string $action): bool
    {
        return (null !== $id)
            && (Response::HTTP_OK === $response->getHttpResponseCode())
            && ($this->isDeleteValuesActionTriggered($action));
    }

    private function isDeleteValuesActionTriggered(string $action): bool
    {
        return $this::DELETE_VALUES_ACTION === $action ?? false;
    }
}
