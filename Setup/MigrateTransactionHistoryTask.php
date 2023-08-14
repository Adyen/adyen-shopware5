<?php

namespace AdyenPayment\Setup;

use Adyen\Core\BusinessLogic\DataAccess\TransactionHistory\Entities\TransactionHistory as TransactionEntity;
use Adyen\Core\BusinessLogic\DataAccess\TransactionLog\Entities\TransactionLog;
use Adyen\Core\BusinessLogic\Domain\Checkout\PaymentRequest\Exceptions\InvalidCurrencyCode;
use Adyen\Core\BusinessLogic\Domain\Checkout\PaymentRequest\Models\Amount\Amount;
use Adyen\Core\BusinessLogic\Domain\Checkout\PaymentRequest\Models\Amount\Currency;
use Adyen\Core\BusinessLogic\Domain\GeneralSettings\Models\CaptureType;
use Adyen\Core\BusinessLogic\Domain\Multistore\StoreContext;
use Adyen\Core\BusinessLogic\Domain\TransactionHistory\Exceptions\InvalidMerchantReferenceException;
use Adyen\Core\BusinessLogic\Domain\TransactionHistory\Models\HistoryItem;
use Adyen\Core\BusinessLogic\Domain\TransactionHistory\Models\TransactionHistory;
use Adyen\Core\BusinessLogic\Domain\TransactionHistory\Services\TransactionHistoryService;
use Adyen\Core\BusinessLogic\Domain\Webhook\Models\Webhook;
use Adyen\Core\BusinessLogic\Domain\Webhook\Services\OrderStatusProvider;
use Adyen\Core\BusinessLogic\TransactionLog\Repositories\TransactionLogRepository;
use Adyen\Core\BusinessLogic\Webhook\Handler\WebhookHandler;
use Adyen\Core\BusinessLogic\Webhook\Tasks\OrderUpdateTask;
use Adyen\Core\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException;
use Adyen\Core\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException;
use Adyen\Core\Infrastructure\ORM\QueryFilter\Operators;
use Adyen\Core\Infrastructure\ORM\QueryFilter\QueryFilter;
use Adyen\Core\Infrastructure\ORM\RepositoryRegistry;
use Adyen\Core\Infrastructure\Serializer\Serializer;
use Adyen\Core\Infrastructure\ServiceRegister;
use Adyen\Core\Infrastructure\TaskExecution\QueueItem;
use Adyen\Core\Infrastructure\TaskExecution\QueueService;
use Adyen\Core\Infrastructure\TaskExecution\Task;
use AdyenPayment\AdyenPayment;
use AdyenPayment\Components\Integration\OrderService;
use AdyenPayment\Repositories\Wrapper\OrderRepository;
use DateInterval;
use DateTime;
use DateTimeInterface;
use Doctrine\DBAL\Connection;
use PDO;
use Shopware\Components\Plugin\CachedConfigReader;
use Shopware\Models\Order\Order;
use Shopware\Models\Shop\Shop;

/**
 * Class MigrateTransactionHistoryTask
 *
 * @package AdyenPayment\Setup
 */
class MigrateTransactionHistoryTask extends Task
{
    private const BATCH_SIZE = 100;

    private $handledOrders = 0;
    private $textNotificationsOffset = 0;

    /**
     * @inheritDoc
     */
    public function serialize()
    {
        return Serializer::serialize($this->toArray());
    }

    /**
     * @inheritDoc
     */
    public function unserialize($serialized)
    {
        $unserialized = Serializer::unserialize($serialized);

        $this->handledOrders = $unserialized['handledOrders'];
        $this->textNotificationsOffset = $unserialized['textNotificationsOffset'];
    }

    /**
     * @inheritDoc
     */
    public static function fromArray(array $array)
    {
        $task = new self();
        $task->handledOrders = $array['handledOrders'];
        $task->textNotificationsOffset = $array['textNotificationsOffset'];

        return $task;
    }

    /**
     * @inheritDoc
     */
    public function toArray()
    {
        return [
            'handledOrders' => $this->handledOrders,
            'textNotificationsOffset' => $this->textNotificationsOffset
        ];
    }

    public function execute(): void
    {
        $orderIds = $this->getOrderIdsForMigration();
        while (!empty($orderIds)) {
            $this->processOrderIdsBatch($orderIds);

            $this->handledOrders += self::BATCH_SIZE;
            $orderIds = $this->getOrderIdsForMigration();
        }

        $this->reportProgress(70);

        $textNotifications = $this->getTextNotifications();
        while (!empty($textNotifications)) {
            $this->processTextNotificationsBatch($textNotifications);

            $this->textNotificationsOffset += self::BATCH_SIZE;
            $textNotifications = $this->getTextNotifications();
        }

        $this->dropLegacyTables();
        $this->reportProgress(100);
    }

    private function processOrderIdsBatch(array $orderIds): void
    {
        foreach ($orderIds as $orderId) {
            $handledNotifications = $this->getHandledNotificationsForOrderId($orderId);
            if (!empty($handledNotifications)) {
                $this->processHandledNotificationsBatch($handledNotifications);
            }

            $this->reportAlive();

            $unhandledNotifications = $this->getUnhandledNotificationsForOrderId($orderId);
            if (!empty($unhandledNotifications)) {
                $this->processUnhandledNotificationsBatch($unhandledNotifications);
            }
        }
    }

    private function dropLegacyTables(): void
    {
        $this->getConnection()->executeQuery(
            'DROP TABLE IF EXISTS `s_plugin_adyen_order_notification`;
                DROP TABLE IF EXISTS `s_plugin_adyen_text_notification`;'
        );
    }

    private function processHandledNotificationsBatch(array $notifications): void
    {
        $ordersMap = $this->getOrderMapFor($notifications);
        /** @var TransactionHistory[] $transactionHistoryMap */
        $transactionHistoryMap = $this->getTransactionHistoryMapFor($notifications, $ordersMap);

        foreach ($notifications as $notification) {
            if (
                !array_key_exists($notification['order_id'], $ordersMap) ||
                !array_key_exists($notification['order_id'], $transactionHistoryMap)
            ) {
                continue;
            }

            $order = $ordersMap[$notification['order_id']];
            $transactionHistory = $transactionHistoryMap[$notification['order_id']];

            StoreContext::doWithStore(
                $order->getShop()->getId(),
                function () use ($notification, $order, $transactionHistory) {
                    $this
                        ->getTransactionLogRepository()
                        ->setTransactionLog($this->transformNotificationToLog($notification, $order));

                    $this->updateTransactionHistoryWith($transactionHistory, $notification, $order);
                }
            );
        }
    }

    private function processUnhandledNotificationsBatch(array $notifications): void
    {
        $ordersMap = $this->getOrderMapFor($notifications);
        /** @var TransactionHistory[] $transactionHistoryMap */
        $transactionHistoryMap = $this->getTransactionHistoryMapFor($notifications, $ordersMap);

        foreach ($notifications as $notification) {
            if (
                !array_key_exists($notification['order_id'], $ordersMap) ||
                !array_key_exists($notification['order_id'], $transactionHistoryMap)
            ) {
                continue;
            }

            $order = $ordersMap[$notification['order_id']];
            $transactionHistory = $transactionHistoryMap[$notification['order_id']];

            StoreContext::doWithStore(
                $order->getShop()->getId(),
                function () use ($notification, $order, $transactionHistory) {
                    $this->getQueueService()->enqueue(
                        'OrderUpdate',
                        new OrderUpdateTask(
                            $this->transformNotificationToWebhook($notification, $order, $transactionHistory)
                        )
                    );
                }
            );
        }
    }

    private function processTextNotificationsBatch(array $textNotifications): void
    {
        $webhooks = array_map(function (array $textNotification) {
            return $this->transformTextNotificationToWebhook(
                (array)json_decode($textNotification['text_notification'], true)
            );
        }, $textNotifications);


        $ordersMap = $this->getOrderMapForWebhooks($webhooks);
        foreach ($webhooks as $webhook) {
            if (!array_key_exists($webhook->getMerchantReference(), $ordersMap)) {
                continue;
            }

            $order = $ordersMap[$webhook->getMerchantReference()];
            // Set webhook merchant reference to temporary id for new processing logic to work as expected
            $webhookForProcessing = $this->cloneWebhookWithNewMerchantReference($webhook, $order->getTemporaryId());

            StoreContext::doWithStore(
                $order->getShop()->getId(),
                function () use ($webhookForProcessing) {
                    $this->getWebhookHandler()->handle($webhookForProcessing);
                }
            );
        }
    }

    /**
     * @return string[]
     */
    private function getOrderIdsForMigration(): array
    {
        $dateLimit = (new DateTime())->sub(new DateInterval('P30D'));
        $query = $this->getConnection()->createQueryBuilder()
            ->select('DISTINCT order_id as order_id')
            ->from('s_plugin_adyen_order_notification', 'notification')
            ->where('notification.created_at >= :createdAt')
            ->setParameter('createdAt', $dateLimit, 'datetime')
            ->setMaxResults(self::BATCH_SIZE)
            ->setFirstResult($this->handledOrders);

        return array_map(static function ($notification) {
            return (string)$notification['order_id'];
        }, $query->execute()->fetchAll(PDO::FETCH_ASSOC));
    }

    /**
     * @param string $orderId
     * @return array
     */
    private function getHandledNotificationsForOrderId(string $orderId): array
    {
        $query = $this->getConnection()->createQueryBuilder()
            ->select('*')
            ->from('s_plugin_adyen_order_notification', 'notification')
            ->where('notification.order_id = :orderId')
            ->andWhere('notification.status IN(:status)')
            ->setParameter('orderId', $orderId)
            ->setParameter('status', ['handled', 'error', 'fatal'], Connection::PARAM_STR_ARRAY)
            ->orderBy('id');

        return $query->execute()->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * @param string $orderId
     * @return array
     */
    private function getUnhandledNotificationsForOrderId(string $orderId): array
    {
        $query = $this->getConnection()->createQueryBuilder()
            ->select('*')
            ->from('s_plugin_adyen_order_notification', 'notification')
            ->where('notification.order_id = :orderId')
            ->andWhere('notification.status NOT IN(:status)')
            ->setParameter('orderId', $orderId)
            ->setParameter('status', ['handled', 'error', 'fatal'], Connection::PARAM_STR_ARRAY)
            ->orderBy('id');

        return $query->execute()->fetchAll(PDO::FETCH_ASSOC);
    }

    private function getTextNotifications(): array
    {
        $query = $this->getConnection()->createQueryBuilder()
            ->select('*')
            ->from('s_plugin_adyen_text_notification', 'notification')
            ->orderBy('id')
            ->setMaxResults(self::BATCH_SIZE)
            ->setFirstResult($this->textNotificationsOffset);

        return $query->execute()->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Transforms notification in the context of the provided Shopware shop
     *
     * @param array $notification
     * @param Order $order
     * @return TransactionLog
     */
    private function transformNotificationToLog(array $notification, Order $order): TransactionLog
    {
        $transactionLog = new TransactionLog();
        $transactionLog->setStoreId((string)$order->getShop()->getId());
        $transactionLog->setMerchantReference((string)$order->getTemporaryId());
        $transactionLog->setExecutionId(0);
        $transactionLog->setEventCode((string)$notification['event_code']);
        $transactionLog->setReason((string)$notification['error_details']);
        $transactionLog->setIsSuccessful((bool)$notification['success']);
        $transactionLog->setTimestamp(
            DateTime::createFromFormat('Y-m-d H:i:s', $notification['created_at'])->getTimestamp()
        );
        $transactionLog->setPaymentMethod((string)$notification['paymentMethod']);
        $transactionLog->setAdyenLink($this->getAdyenLink($notification['psp_reference'], $order->getShop()));
        $transactionLog->setShopLink(
            $this->getOrderService()->getOrderUrlForId((int)$notification['order_id'])
        );
        $transactionLog->setQueueStatus(
            $notification['status'] === 'handled' ? QueueItem::COMPLETED : QueueItem::FAILED
        );
        $transactionLog->setPspReference($notification['psp_reference']);

        return $transactionLog;
    }

    /**
     * @throws InvalidMerchantReferenceException
     */
    private function updateTransactionHistoryWith(
        TransactionHistory $transactionHistory,
        array $notification,
        Order $order
    ): void {
        $webhook = $this->transformNotificationToWebhook($notification, $order, $transactionHistory);

        $transactionHistory->add(
            new HistoryItem(
                $webhook->getPspReference(),
                $webhook->getMerchantReference(),
                $webhook->getEventCode(),
                $this->getOrderStatusProvider()->getNewPaymentState($webhook, $transactionHistory),
                $webhook->getEventDate(),
                $webhook->isSuccess(),
                $webhook->getAmount(),
                $webhook->getPaymentMethod(),
                $webhook->getRiskScore(),
                $webhook->isLive()
            )
        );

        $this->getTransactionHistoryService()->setTransactionHistory($transactionHistory);
    }

    private function transformNotificationToWebhook(
        array $notification,
        Order $order,
        TransactionHistory $transactionHistory
    ): Webhook {
        $config = $this->getCachedConfigReader()->getByPluginName(AdyenPayment::NAME, $order->getShop());
        return new Webhook(
            Amount::fromInt(
                (int)$notification['amount_value'],
                !empty($notification['amount_currency']) ? Currency::fromIsoCode(
                    $notification['amount_currency']
                ) : Currency::getDefault()
            ),
            (string)$notification['event_code'],
            DateTime::createFromFormat('Y-m-d H:i:s', $notification['created_at'])->format(DateTimeInterface::ATOM),
            '',
            '',
            (string)$order->getTemporaryId(),
            (string)$notification['psp_reference'],
            (string)$notification['paymentMethod'],
            (string)$notification['error_details'],
            (bool)$notification['success'],
            $transactionHistory->getOriginalPspReference(),
            0,
            !empty($config['environment']) && 'LIVE' === mb_strtoupper($config['environment'])
        );
    }

    private function transformTextNotificationToWebhook(array $textNotification): Webhook
    {
        return new Webhook(
            Amount::fromInt(
                $textNotification['amount']['value'] ?? 0,
                $textNotification['amount']['currency'] ? Currency::fromIsoCode(
                    $textNotification['amount']['currency']
                ) : Currency::getDefault()
            ),
            $textNotification['eventCode'] ?? '',
            $textNotification['eventDate'] ?? '',
            $textNotification['additionalData']['hmacSignature'] ?? '',
            $textNotification['merchantAccountCode'] ?? '',
            $textNotification['merchantReference'] ?? '',
            $textNotification['pspReference'] ?? '',
            $textNotification['paymentMethod'] ?? '',
            $textNotification['reason'] ?? '',
            $textNotification['success'] === 'true',
            $textNotification['originalReference'] ?? '',
            $textNotification['additionalData']['totalFraudScore'] ?? 0,
            $textNotification['live'] === 'true'
        );
    }

    /**
     * Gets the same webhook instance as provided one but with changed merchant reference from parameter
     *
     * @param Webhook $webhook
     * @param string $merchantReference
     * @return Webhook
     */
    private function cloneWebhookWithNewMerchantReference(Webhook $webhook, string $merchantReference): Webhook
    {
        return new Webhook(
            $webhook->getAmount(),
            $webhook->getEventCode(),
            $webhook->getEventDate(),
            $webhook->getHmacSignature(),
            $webhook->getMerchantAccountCode(),
            $merchantReference,
            $webhook->getPspReference(),
            $webhook->getPaymentMethod(),
            $webhook->getReason(),
            $webhook->isSuccess(),
            $webhook->getOriginalReference(),
            $webhook->getRiskScore(),
            $webhook->isLive()
        );
    }

    private function getAdyenLink(string $pspReference, Shop $shop): string
    {
        $domain = 'ca-test.adyen.com';

        $config = $this->getCachedConfigReader()->getByPluginName(AdyenPayment::NAME, $shop);
        if (!empty($config['environment']) && 'LIVE' === mb_strtoupper($config['environment'])) {
            $domain = 'ca-live.adyen.com';
        }

        return "https://$domain/ca/ca/config/event-logs.shtml?query=$pspReference";
    }

    /**
     * @param array $notifications
     * @return Order[]
     */
    private function getOrderMapFor(array $notifications): array
    {
        return $this->getOrderRepository()->getOrdersByIds(
            array_map(static function (array $notification) {
                return $notification['order_id'];
            }, $notifications)
        );
    }

    /**
     * @param Webhook[] $webhooks
     * @return Order[]
     */
    private function getOrderMapForWebhooks(array $webhooks): array
    {
        return $this->getOrderRepository()->getOrdersByNumbers(
            array_map(static function (Webhook $webhook) {
                return $webhook->getMerchantReference();
            }, $webhooks)
        );
    }

    /**
     * @param array $notifications
     * @param Order[] $ordersMap
     *
     * @return array
     *
     * @throws InvalidMerchantReferenceException
     * @throws QueryFilterInvalidParamException
     * @throws RepositoryNotRegisteredException
     * @throws InvalidCurrencyCode
     */
    private function getTransactionHistoryMapFor(array $notifications, array $ordersMap): array
    {
        $ordersByTempIdMap = [];
        foreach ($ordersMap as $order) {
            $ordersByTempIdMap[$order->getTemporaryId()] = $order;
        }

        $queryFilter = new QueryFilter();
        $queryFilter
            ->where(
                'merchantReference',
                Operators::IN,
                array_map(
                    static function (array $notification) use ($ordersMap) {
                        $merchantReference = '';
                        if (array_key_exists($notification['order_id'], $ordersMap)) {
                            $merchantReference = $ordersMap[$notification['order_id']]->getTemporaryId();
                        }

                        return $merchantReference;
                    },
                    $notifications
                )
            );

        /** @var TransactionEntity[] $entities */
        $entities = RepositoryRegistry::getRepository(TransactionEntity::class)->select($queryFilter);
        $transactionHistoryMap = [];
        foreach ($entities as $transactionHistoryEntity) {
            $transactionHistory = $transactionHistoryEntity->getTransactionHistory();
            $order = $ordersByTempIdMap[$transactionHistory->getMerchantReference()];
            $transactionHistoryMap[$order->getId()] = $transactionHistory;
        }

        // Ensure that each notification has its transaction history initialized if DB does not have it until now
        foreach ($notifications as $notification) {
            if (!array_key_exists($notification['order_id'], $ordersMap)) {
                continue;
            }

            $merchantReference = $ordersMap[$notification['order_id']]->getTemporaryId();
            if (!array_key_exists($notification['order_id'], $transactionHistoryMap)) {
                $transactionHistoryMap[$notification['order_id']] = new TransactionHistory(
                    $merchantReference,
                    CaptureType::unknown(),
                    0,
                    Currency::fromIsoCode($ordersMap[$notification['order_id']]->getCurrency())
                );
            }
        }

        return $transactionHistoryMap;
    }

    /**
     * @return Connection
     */
    private function getConnection(): Connection
    {
        return Shopware()->Container()->get('dbal_connection');
    }

    /**
     * @return CachedConfigReader
     */
    private function getCachedConfigReader(): CachedConfigReader
    {
        return Shopware()->Container()->get('shopware.plugin.cached_config_reader');
    }

    /**
     * @return OrderRepository
     */
    private function getOrderRepository(): OrderRepository
    {
        return Shopware()->Container()->get(OrderRepository::class);
    }

    /**
     * @return OrderService
     */
    private function getOrderService(): OrderService
    {
        return Shopware()->Container()->get(OrderService::class);
    }

    /**
     * @return TransactionLogRepository
     */
    private function getTransactionLogRepository(): TransactionLogRepository
    {
        return ServiceRegister::getService(TransactionLogRepository::class);
    }

    /**
     * @return TransactionHistoryService
     */
    private function getTransactionHistoryService(): TransactionHistoryService
    {
        return ServiceRegister::getService(TransactionHistoryService::class);
    }

    /**
     * @return OrderStatusProvider
     */
    private function getOrderStatusProvider(): OrderStatusProvider
    {
        return ServiceRegister::getService(OrderStatusProvider::class);
    }

    /**
     * @return QueueService
     */
    private function getQueueService(): QueueService
    {
        return ServiceRegister::getService(QueueService::class);
    }

    /**
     * @return WebhookHandler
     */
    private function getWebhookHandler(): WebhookHandler
    {
        return ServiceRegister::getService(WebhookHandler::class);
    }
}
