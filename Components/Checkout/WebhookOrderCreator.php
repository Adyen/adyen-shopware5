<?php

namespace AdyenPayment\Components\Checkout;

use Adyen\Core\BusinessLogic\Domain\Checkout\PaymentRequest\Models\PaymentMethodCode;
use Adyen\Core\BusinessLogic\Domain\Webhook\Models\Webhook;
use Adyen\Core\Infrastructure\Logger\Logger;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\Exception\NotSupported;
use Exception;
use Shopware\Components\Model\ModelManager;
use Shopware\Components\ShopRegistrationServiceInterface;
use Shopware\Models\Shop\Shop;
use Shopware_Components_Modules;

/**
 * Class WebhookOrderCreator
 *
 * Creates the Shopware order from the stored checkout snapshot when the AUTHORISATION webhook finds no order.
 * Mirrors Shopware_Controllers_Frontend_Payment::saveOrder(), which cannot be reused because it reads the
 * shopper session and the HTTP request, neither of which exists in a webhook.
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
     * Only payment methods that return Pending with an action the shopper completes in another app are created
     * from the webhook. For every other method the order is created by the shopper's own request, as before.
     *
     * The constant is checked directly rather than through PaymentMethodCode::parse(), which throws for codes
     * outside the supported set, such as the card brands notifications carry.
     *
     * @param Webhook $webhook
     *
     * @return bool
     */
    public function supports(Webhook $webhook): bool
    {
        return in_array(
            $webhook->getPaymentMethod(),
            PaymentMethodCode::WEBHOOK_ORDER_CREATION_SUPPORTED,
            true
        );
    }

    /**
     * @param Webhook $webhook
     *
     * @return bool True when the order exists afterwards, whether it was created here or by the checkout.
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
            // The checkout releases the snapshot as soon as it has created the order, so a missing snapshot most
            // often means the order is already there.
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

                    $this->registerShop($orderInputs);

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

        $this->cleanUpCheckoutLeftovers($user, $basket);

        // The snapshot is deliberately left in place. A browser tab that was suspended while the shopper
        // approved the payment needs it to restore its session and reach the confirmation page for this order.
        // The checkout path removes it once it runs, and CheckoutSnapshotRepository purges it after the retention
        // period if nobody ever returns.

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
     * sSaveOrder() writes the registered shop into s_order.language and its main shop into s_order.subshopID.
     *
     * @param array $orderInputs
     *
     * @return void
     * @throws NotSupported
     */
    private function registerShop(array $orderInputs): void
    {
        $shopId = (int)($orderInputs[CheckoutSnapshotRepository::INPUT_SHOP_ID] ?? 0);
        $shop = $this->modelManager->getRepository(Shop::class)->getActiveById($shopId);

        if ($shop === null) {
            Logger::logWarning('Shop ' . $shopId . ' from the checkout snapshot is not active.', 'Integration');

            return;
        }

        $this->shopRegistrationService->registerShop($shop);
    }

    /**
     * sSaveOrder() empties the cart and removes the cancelled placeholder order through the session id of the
     * request it runs in. A webhook does not have the shopper session, so both are done here with the session id
     * recorded in the snapshot.
     *
     * @param array $user
     * @param array $basket
     *
     * @return void
     */
    private function cleanUpCheckoutLeftovers(array $user, array $basket): void
    {
        $sessionId = (string)($user['additional']['user']['sessionID'] ?? '');

        if ($sessionId === '') {
            return;
        }

        try {
            // Only the rows that went into this order. The shopper may have put new items in the cart since,
            // in the same session, and those must survive.
            $basketIds = array_values(array_filter(array_map(
                static function ($item) {
                    return (int)($item['id'] ?? 0);
                },
                $basket['content'] ?? []
            )));

            if (!empty($basketIds)) {
                $this->connection->executeUpdate(
                    'DELETE FROM s_order_basket WHERE sessionID = :sessionId AND id IN (:basketIds)',
                    [':sessionId' => $sessionId, ':basketIds' => $basketIds],
                    [':basketIds' => Connection::PARAM_INT_ARRAY]
                );
            }

            $this->connection->executeUpdate(
                'DELETE d FROM s_order_details d INNER JOIN s_order o ON o.id = d.orderID
                 WHERE o.temporaryID = :sessionId AND o.status = -1',
                [':sessionId' => $sessionId]
            );
            $this->connection->executeUpdate(
                'DELETE FROM s_order WHERE temporaryID = :sessionId AND status = -1',
                [':sessionId' => $sessionId]
            );
        } catch (Exception $exception) {
            Logger::logWarning(
                'Cleaning up the checkout leftovers failed: ' . $exception->getMessage(),
                'Integration'
            );
        }
    }
}
