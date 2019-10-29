<?php

namespace MeteorAdyen\Components\Builder;

use Adyen\Util\Currency;
use MeteorAdyen\Models\Notification;
use MeteorAdyen\Models\Enum\NotificationStatus;
use Shopware\Components\Model\ModelManager;
use Shopware\Models\Order\Order;

/**
 * Class NotificationBuilder
 * @package MeteorAdyen\Components\Builder
 */
class NotificationBuilder
{
    /**
     * @var Currency
     */
    private $currency;
    /**
     * @var ModelManager
     */
    private $modelManager;
    /**
     * @var \Doctrine\Common\Persistence\ObjectRepository|\Doctrine\ORM\EntityRepository|\Shopware\Models\Order\Repository
     */
    private $orderRepository;

    /**
     * NotificationBuilder constructor.
     * @param Currency $currency
     * @param ModelManager $modelManager
     */
    public function __construct(
        Currency $currency,
        ModelManager $modelManager
    ) {
        $this->currency = $currency;
        $this->modelManager = $modelManager;
        $this->orderRepository = $modelManager->getRepository(Order::class);
    }

    /**
     * Builds Notification object from Adyen webhook params
     *
     * @param $params
     * @return Notification
     */
    public function fromParams($params)
    {
        $notification = new Notification();

        $notification->setStatus(NotificationStatus::STATUS_RECEIVED);

        if (isset($params['merchantReference'])) {
            /** @var Order $order */
            $order = $this->orderRepository->findOneBy(['number' => $params['merchantReference']]);
            $notification->setOrder($order);
        }
        if (isset($params['pspReference'])) {
            $notification->setPspReference($params['pspReference']);
        }
        if (isset($params['eventCode'])) {
            $notification->setEventCode($params['eventCode']);
        }
        if (isset($params['success'])) {
            $notification->setSuccess($params['success'] == 'true');
        }
        if (isset($params['merchantAccountCode'])) {
            $notification->setMerchantAccountCode($params['merchantAccountCode']);
        }
        if (isset($params['amount']['value']) && isset($params['amount']['currency'])) {
            $value = $params['amount']['value'];
            $currency = $params['amount']['currency'];

            $value = $this->currency->formatAmount($value, $currency);

            $notification->setAmountValue($value);
            $notification->setAmountCurrency($params['amount']['currency']);
        }
        if (isset($params['reason'])) {
            $notification->setErrorDetails($params['reason']);
        }

        return $notification;
    }
}
