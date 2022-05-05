<?php

declare(strict_types=1);

namespace AdyenPayment\Components\Builder;

use Adyen\Util\Currency;
use AdyenPayment\Exceptions\InvalidParameterException;
use AdyenPayment\Exceptions\OrderNotFoundException;
use AdyenPayment\Models\Enum\NotificationStatus;
use AdyenPayment\Models\Notification;
use Shopware\Components\Model\ModelManager;
use Shopware\Models\Order\Order;

/**
 * Class NotificationBuilder.
 */
class NotificationBuilder
{
    /**
     * @var ModelManager
     */
    private $modelManager;

    /**
     * @var \Doctrine\ORM\EntityRepository|\Doctrine\Persistence\ObjectRepository|\Shopware\Models\Order\Repository
     */
    private $orderRepository;

    /**
     * @var Currency
     */
    private $currency;

    /**
     * NotificationBuilder constructor.
     */
    public function __construct(
        ModelManager $modelManager
    ) {
        $this->modelManager = $modelManager;
        $this->orderRepository = $modelManager->getRepository(Order::class);
        $this->currency = new Currency();
    }

    /**
     * Builds Notification object from Adyen webhook params.
     *
     * @param $params
     *
     * @throws OrderNotFoundException
     * @throws InvalidParameterException
     */
    public function fromParams($params): Notification
    {
        $notification = new Notification();

        $notification->setStatus(NotificationStatus::STATUS_RECEIVED);

        if (!isset($params['merchantReference'])) {
            throw InvalidParameterException::missingParameter('merchantReference');
        }

        /** @var Order $order */
        $order = $this->orderRepository->findOneBy(['number' => $params['merchantReference']]);
        if (!$order) {
            throw new OrderNotFoundException($params['merchantReference']);
        }

        $notification->setOrder($order);
        $notification->setOrderId($order->getId());

        if (isset($params['pspReference'])) {
            $notification->setPspReference($params['pspReference']);
        }
        if (isset($params['eventCode'])) {
            $notification->setEventCode($params['eventCode']);
        }

        if (isset($params['paymentMethod'])) {
            $notification->setPaymentMethod($params['paymentMethod']);
        }

        if (!isset($params['paymentMethod']) && isset($params['additionalData']['paymentMethodVariant'])) {
            $notification->setPaymentMethod($params['additionalData']['paymentMethodVariant']);
        }

        if (isset($params['success'])) {
            $notification->setSuccess('true' === $params['success']);
        }
        if (isset($params['merchantAccountCode'])) {
            $notification->setMerchantAccountCode($params['merchantAccountCode']);
        }
        if (isset($params['amount']['value'], $params['amount']['currency'])) {
            $notification->setAmountValue($params['amount']['value']);
            $notification->setAmountCurrency($params['amount']['currency']);
        }
        if (isset($params['reason'])) {
            $notification->setErrorDetails($params['reason']);
        }

        if (isset($params['eventCode'], $params['success'])) {
            $notification->setScheduledProcessingTime($this->getProcessingTime($notification));
        }

        return $notification;
    }

    /**
     * Set delay in processing time for certain notifications.
     */
    private function getProcessingTime(Notification $notification): \DateTime
    {
        $processingTime = new \DateTime();
        switch ($notification->getEventCode()) {
            case 'AUTHORISATION':
                if (!$notification->isSuccess()) {
                    $processingTime = $processingTime->add(new \DateInterval('PT30M'));
                }

                break;
            case 'OFFER_CLOSED':
                $processingTime = $processingTime->add(new \DateInterval('PT30M'));

                break;
            default:
                break;
        }

        return $processingTime;
    }
}
