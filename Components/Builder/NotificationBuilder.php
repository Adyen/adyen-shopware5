<?php

declare(strict_types=1);

namespace MeteorAdyen\Components\Builder;

use Adyen\Util\Currency;
use MeteorAdyen\Exceptions\InvalidParameterException;
use MeteorAdyen\Exceptions\OrderNotFoundException;
use MeteorAdyen\Models\Enum\NotificationStatus;
use MeteorAdyen\Models\Notification;
use Shopware\Components\Model\ModelManager;
use Shopware\Models\Order\Order;

/**
 * Class NotificationBuilder
 * @package MeteorAdyen\Components\Builder
 */
class NotificationBuilder
{
    /**
     * @var ModelManager
     */
    private $modelManager;
    /**
     * @var \Doctrine\Common\Persistence\ObjectRepository|\Doctrine\ORM\EntityRepository|\Shopware\Models\Order\Repository
     */
    private $orderRepository;
    /**
     * @var Currency
     */
    private $currency;

    /**
     * NotificationBuilder constructor.
     * @param ModelManager $modelManager
     */
    public function __construct(
        ModelManager $modelManager
    ) {
        $this->modelManager = $modelManager;
        $this->orderRepository = $modelManager->getRepository(Order::class);
        $this->currency = new Currency();
    }

    /**
     * Builds Notification object from Adyen webhook params
     *
     * @param $params
     * @return Notification|void
     * @throws OrderNotFoundException
     * @throws InvalidParameterException
     */
    public function fromParams($params)
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

        if (isset($params['pspReference'])) {
            $notification->setPspReference($params['pspReference']);
        }
        if (isset($params['eventCode'])) {
            $notification->setEventCode($params['eventCode']);
        }

        if (isset($params['paymentMethod'])) {
            $notification->setPaymentMethod($params['paymentMethod']);
        }

        if (isset($params['success'])) {
            $notification->setSuccess($params['success'] == 'true');
        }
        if (isset($params['merchantAccountCode'])) {
            $notification->setMerchantAccountCode($params['merchantAccountCode']);
        }
        if (isset($params['amount']['value']) && isset($params['amount']['currency'])) {
            $notification->setAmountValue($params['amount']['value']);
            $notification->setAmountCurrency($params['amount']['currency']);
        }
        if (isset($params['reason'])) {
            $notification->setErrorDetails($params['reason']);
        }

        return $notification;
    }
}
