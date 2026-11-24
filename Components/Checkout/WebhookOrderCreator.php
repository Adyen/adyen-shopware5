<?php

namespace AdyenPayment\Components\Checkout;

use Adyen\Core\BusinessLogic\Domain\Webhook\Models\Webhook;
use Adyen\Core\Infrastructure\Logger\Logger;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\Exception\NotSupported;
use Exception;
use Shopware\Components\Model\ModelManager;
use Shopware\Components\ShopRegistrationServiceInterface;
use Shopware\Models\Order\Status;
use Shopware\Models\Shop\Shop;
use Shopware_Components_Modules;

/**
 * Class WebhookOrderCreator
 *
 * Creates the order from the checkout snapshot when the AUTHORISATION webhook finds none.
 * Mirrors Shopware_Controllers_Frontend_Payment::saveOrder(), which needs the shopper session.
 *
 * @package AdyenPayment\Components\Checkout
 */
class WebhookOrderCreator
{
    /**
     * @var CheckoutSnapshotRepository
     */
    private $snapshotRepository;

    /**
     * @var ModelManager
     */
    private $modelManager;

    /**
     * @var Shopware_Components_Modules
     */
    private $modules;

    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var OrderCreationLock
     */
    private $lock;

    /**
     * @var ShopRegistrationServiceInterface
     */
    private $shopRegistrationService;

    /**
     * @param CheckoutSnapshotRepository $snapshotRepository
     * @param ModelManager $modelManager
     * @param Shopware_Components_Modules $modules
     * @param Connection $connection
     * @param OrderCreationLock $lock
     * @param ShopRegistrationServiceInterface $shopRegistrationService
     */
    public function __construct(
        CheckoutSnapshotRepository $snapshotRepository,
        ModelManager $modelManager,
        Shopware_Components_Modules $modules,
        Connection $connection,
        OrderCreationLock $lock,
        ShopRegistrationServiceInterface $shopRegistrationService
    ) {
        $this->snapshotRepository = $snapshotRepository;
        $this->modelManager = $modelManager;
        $this->modules = $modules;
        $this->connection = $connection;
        $this->lock = $lock;
        $this->shopRegistrationService = $shopRegistrationService;
    }

    /**
     * @param Webhook $webhook
     *
     * @return bool True when the order exists afterwards.
     */
    public function create(Webhook $webhook): bool
    {
        $orderReference = $webhook->getMerchantReference();

        try {
            $snapshot = $this->snapshotRepository->load($orderReference);
        } catch (Exception $exception) {
            Logger::logError(
                'Reading the checkout snapshot for merchant reference ' . $orderReference . ' failed: ' .
                $exception->getMessage(),
                'Integration'
            );

            return false;
        }

        if ($snapshot === null) {
            // The checkout releases the snapshot once it has created the order.
            if ($this->lock->orderExists($orderReference)) {
                return true;
            }

            Logger::logWarning(
                'No checkout snapshot available for merchant reference ' . $orderReference .
                '. The order cannot be created from the webhook.',
                'Integration'
            );

            return false;
        }

        $orderVariables = CheckoutSnapshotRepository::orderVariablesOf($snapshot);
        $orderInputs = CheckoutSnapshotRepository::orderInputsOf($snapshot);

        $user = $orderVariables['sUserData'] ?? [];
        $basket = $orderVariables['sBasket'] ?? [];

        if (empty($user) || empty($basket)) {
            Logger::logError(
                'Checkout snapshot for merchant reference ' . $orderReference . ' is incomplete.',
                'Integration'
            );

            return false;
        }

        try {
            $result = $this->lock->synchronized(
                $orderReference,
                function () use ($webhook, $orderVariables, $orderInputs, $user, $basket, $orderReference) {
                    if ($this->lock->orderExists($orderReference)) {
                        return true;
                    }

                    $this->registerShop($orderInputs, $basket);

                    return $this->saveOrder($webhook, $orderVariables, $orderInputs, $user, $basket);
                },
                false
            );
        } catch (Exception $exception) {
            Logger::logError(
                'Creating the order for merchant reference ' . $orderReference . ' failed: ' .
                $exception->getMessage(),
                'Integration'
            );

            return false;
        }

        if ($result === true) {
            return true;
        }

        if (empty($result)) {
            Logger::logWarning(
                'No order was created from the webhook for merchant reference ' . $orderReference .
                '. Either the lock could not be acquired, or saving it failed.',
                'Integration'
            );

            return false;
        }

        Logger::logWarning(
            'Order ' . $result . ' created from the webhook for merchant reference ' . $orderReference . '.',
            'Integration'
        );

        $this->cleanUpCheckoutLeftovers($user, $basket, $orderInputs);

        // Re-read after saving: Cancel marks the snapshot under the same lock and only while no order exists, so
        // a mark that is not visible now was never written.
        if ($this->wasCancelledByShopper($orderReference)) {
            $this->flagForClarification((string)$result, $orderReference);
        }

        // The snapshot stays, so a returning browser can still reach the confirmation page.

        return true;
    }

    /**
     * @param Webhook $webhook
     * @param array $orderVariables
     * @param array $orderInputs
     * @param array $user
     * @param array $basket
     *
     * @return string|null
     * @throws \Enlight_Exception
     */
    private function saveOrder(
        Webhook $webhook,
        array $orderVariables,
        array $orderInputs,
        array $user,
        array $basket
    ): ?string {
        $order = $this->modules->Order();

        $order->sUserData = $user;
        $order->sComment = (string)($orderInputs[CheckoutSnapshotRepository::INPUT_COMMENT] ?? '');
        $order->sBasketData = $basket;
        $order->sAmount = $basket['sAmount'] ?? 0;
        $order->sAmountWithTax = !empty($basket['AmountWithTaxNumeric'])
            ? $basket['AmountWithTaxNumeric']
            : ($basket['AmountNumeric'] ?? 0);
        $order->sAmountNet = $basket['AmountNetNumeric'] ?? 0;
        $order->sShippingcosts = $basket['sShippingcosts'] ?? 0;
        $order->sShippingcostsNumeric = $basket['sShippingcostsWithTax'] ?? 0;
        $order->sShippingcostsNumericNet = $basket['sShippingcostsNet'] ?? 0;
        $order->bookingId = $webhook->getPspReference();
        $order->dispatchId = (int)($orderInputs[CheckoutSnapshotRepository::INPUT_DISPATCH_ID] ?? 0);
        $order->sNet = empty($user['additional']['charge_vat']);
        $order->uniqueID = $webhook->getMerchantReference();
        $order->deviceType = (string)($orderInputs[CheckoutSnapshotRepository::INPUT_DEVICE_TYPE] ?? '');

        return $order->sSaveOrder();
    }

    /**
     * sSaveOrder() takes language and subshop from the registered shop, and the mail takes its currency.
     *
     * @param array $orderInputs
     * @param array $basket
     *
     * @return void
     * @throws NotSupported
     */
    private function registerShop(array $orderInputs, array $basket): void
    {
        $shopId = (int)($orderInputs[CheckoutSnapshotRepository::INPUT_SHOP_ID] ?? 0);
        $shop = $this->modelManager->getRepository(Shop::class)->getActiveById($shopId);

        if ($shop === null) {
            Logger::logWarning('Shop ' . $shopId . ' from the checkout snapshot is not active.', 'Integration');

            return;
        }

        $currencyId = (int)($basket['sCurrencyId'] ?? 0);
        foreach ($shop->getCurrencies() as $currency) {
            if ($currency->getId() === $currencyId) {
                $shop->setCurrency($currency);
                break;
            }
        }

        $this->shopRegistrationService->registerShop($shop);
    }

    /**
     * @param string $orderReference
     *
     * @return bool
     */
    private function wasCancelledByShopper(string $orderReference): bool
    {
        try {
            $snapshot = $this->snapshotRepository->load($orderReference);
        } catch (Exception $exception) {
            Logger::logWarning(
                'Reading the cancellation mark for merchant reference ' . $orderReference . ' failed: ' .
                $exception->getMessage(),
                'Integration'
            );

            return false;
        }

        return $snapshot !== null && CheckoutSnapshotRepository::isCancelled($snapshot);
    }

    /**
     * The shopper may have paid the same cart again with another method after Cancel, so the order is held for
     * the merchant to check for a duplicate before it is shipped.
     *
     * @param string $orderNumber
     * @param string $orderReference
     *
     * @return void
     */
    private function flagForClarification(string $orderNumber, string $orderReference): void
    {
        $comment = 'The payment was approved after the shopper cancelled the payment page. '
            . 'Check for a duplicate order from the same customer before shipping.';

        try {
            $orderId = (int)$this->connection->fetchColumn(
                'SELECT id FROM s_order WHERE ordernumber = :orderNumber',
                [':orderNumber' => $orderNumber]
            );

            if ($orderId === 0) {
                return;
            }

            $this->connection->executeUpdate(
                'UPDATE s_order SET internalcomment = :comment WHERE id = :orderId',
                [':comment' => $comment, ':orderId' => $orderId]
            );

            $this->modules->Order()->setOrderStatus(
                $orderId,
                Status::ORDER_STATE_CLARIFICATION_REQUIRED,
                false,
                $comment
            );

            Logger::logWarning(
                'Order ' . $orderNumber . ' for merchant reference ' . $orderReference .
                ' was paid after the shopper cancelled and is marked as clarification required.',
                'Integration'
            );
        } catch (Exception $exception) {
            Logger::logWarning(
                'Marking order ' . $orderNumber . ' as clarification required failed: ' . $exception->getMessage(),
                'Integration'
            );
        }
    }

    /**
     * Does what sSaveOrder() does through the shopper session: empties the cart and removes the placeholder order.
     *
     * @param array $user
     * @param array $basket
     * @param array $orderInputs
     *
     * @return void
     */
    private function cleanUpCheckoutLeftovers(array $user, array $basket, array $orderInputs): void
    {
        $sessionId = (string)($user['additional']['user']['sessionID'] ?? '');

        // Only the items of this order, not ones added since.
        $basketIds = array_values(array_filter(array_map(
            static function ($item) {
                return (int)($item['id'] ?? 0);
            },
            $basket['content'] ?? []
        )));

        // Only the placeholders from when this payment started, not one from a newer checkout.
        $temporaryOrderIds = array_values(array_filter(array_map(
            'intval',
            (array)($orderInputs[CheckoutSnapshotRepository::INPUT_TEMPORARY_ORDER_IDS] ?? [])
        )));

        try {
            if ($sessionId !== '' && !empty($basketIds)) {
                $this->connection->executeUpdate(
                    'DELETE FROM s_order_basket WHERE sessionID = :sessionId AND id IN (:basketIds)',
                    [':sessionId' => $sessionId, ':basketIds' => $basketIds],
                    [':basketIds' => Connection::PARAM_INT_ARRAY]
                );
            }

            if (!empty($temporaryOrderIds)) {
                $this->connection->executeUpdate(
                    'DELETE d FROM s_order_details d INNER JOIN s_order o ON o.id = d.orderID
                     WHERE o.id IN (:orderIds) AND o.status = -1',
                    [':orderIds' => $temporaryOrderIds],
                    [':orderIds' => Connection::PARAM_INT_ARRAY]
                );
                $this->connection->executeUpdate(
                    'DELETE FROM s_order WHERE id IN (:orderIds) AND status = -1',
                    [':orderIds' => $temporaryOrderIds],
                    [':orderIds' => Connection::PARAM_INT_ARRAY]
                );
            }
        } catch (Exception $exception) {
            Logger::logWarning(
                'Cleaning up the checkout leftovers failed: ' . $exception->getMessage(),
                'Integration'
            );
        }
    }
}
