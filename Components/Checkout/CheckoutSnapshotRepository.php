<?php

namespace AdyenPayment\Components\Checkout;

use Adyen\Core\Infrastructure\Logger\Logger;
use DateTime;
use Doctrine\DBAL\Connection;
use Exception;
use Shopware\Components\BasketSignature\BasketPersister;

/**
 * Class CheckoutSnapshotRepository
 *
 * Stores the checkout snapshot under the Adyen order reference so that the AUTHORISATION webhook can create the
 * order when the shopper never returned from the payment method. The snapshot is the same sOrderVariables array
 * that Shopware persists for its own basket signature, kept in the same table under a different key.
 *
 * @package AdyenPayment\Components\Checkout
 */
class CheckoutSnapshotRepository
{
    private const SIGNATURE_KEY = 'signature';
    private const VARIABLES_KEY = 'orderVariables';
    private const ORDER_INPUTS_KEY = 'orderInputs';

    public const INPUT_SHOP_ID = 'shopId';
    public const INPUT_COMMENT = 'comment';
    public const INPUT_DISPATCH_ID = 'dispatchId';
    public const INPUT_DEVICE_TYPE = 'deviceType';

    /**
     * Webhooks for a payment can arrive days after the attempt (Adyen keeps retrying failed deliveries), so the
     * snapshot is kept for a while after it was written. Shopware stores only the date, not the time.
     */
    private const RETENTION_DAYS = 10;

    /**
     * Rows are keyed by this prefix plus the order reference, so this plugin's rows can be told apart from
     * Shopware's own basket signatures (and other plugins') in the shared table.
     */
    private const KEY_PREFIX = 'adyen_snapshot_';

    /**
     * @var BasketPersister
     */
    private $persister;

    /**
     * @var Connection
     */
    private $connection;

    /**
     * @param BasketPersister $persister
     * @param Connection $connection
     */
    public function __construct(BasketPersister $persister, Connection $connection)
    {
        $this->persister = $persister;
        $this->connection = $connection;
    }

    /**
     * The basket signature is stored beside the checkout so that a request presenting a reference can be held
     * against it. The reference alone travels in URLs and is not a secret.
     *
     * Order inputs are the values Shopware's saveOrder() takes from the session and request rather than from
     * sOrderVariables (see the INPUT_* keys), captured so an order created without the shopper can use them too.
     *
     * @param string $orderReference
     * @param array $orderVariables
     * @param string $basketSignature
     * @param array $orderInputs
     *
     * @return void
     * @throws \Exception
     */
    public function store(
        string $orderReference,
        array $orderVariables,
        string $basketSignature,
        array $orderInputs = []
    ): void {
        if ($orderReference === '' || empty($orderVariables) || $basketSignature === '') {
            return;
        }

        $this->persister->persist(self::KEY_PREFIX . $orderReference, [
            self::SIGNATURE_KEY => $basketSignature,
            self::VARIABLES_KEY => $orderVariables,
            self::ORDER_INPUTS_KEY => $orderInputs,
        ]);

        $this->purgeExpired();
    }

    /**
     * Removes this plugin's snapshots older than the retention period. Shopware has no cleanup for this table, so
     * without it every abandoned or webhook-only attempt would keep the shopper's data forever. It runs on every
     * write rather than from a cron job, so it does not depend on the shop's cron being configured.
     *
     * @return void
     */
    private function purgeExpired(): void
    {
        try {
            $this->connection->executeUpdate(
                'DELETE FROM ' . BasketPersister::DBAL_TABLE
                . ' WHERE signature LIKE :keyPrefix AND created_at < :cutoff',
                [
                    ':keyPrefix' => addcslashes(self::KEY_PREFIX, '_%') . '%',
                    ':cutoff' => (new DateTime('-' . self::RETENTION_DAYS . ' days'))->format('Y-m-d'),
                ]
            );
        } catch (Exception $exception) {
            Logger::logWarning(
                'Removing expired checkout snapshots failed: ' . $exception->getMessage(),
                'Integration'
            );
        }
    }

    /**
     * Reads the snapshot without removing it, so the shopper's own flow can restore its session from it and
     * a later webhook still finds it.
     *
     * @param string $orderReference
     *
     * @return array|null
     */
    public function load(string $orderReference): ?array
    {
        if ($orderReference === '') {
            return null;
        }

        $snapshot = $this->persister->load(self::KEY_PREFIX . $orderReference);

        if (!is_array($snapshot)
            || empty($snapshot[self::SIGNATURE_KEY])
            || empty($snapshot[self::VARIABLES_KEY])
        ) {
            return null;
        }

        return $snapshot;
    }

    /**
     * @param array $snapshot
     *
     * @return string
     */
    public static function signatureOf(array $snapshot): string
    {
        return (string)$snapshot[self::SIGNATURE_KEY];
    }

    /**
     * @param array $snapshot
     *
     * @return array
     */
    public static function orderVariablesOf(array $snapshot): array
    {
        return (array)$snapshot[self::VARIABLES_KEY];
    }

    /**
     * @param array $snapshot
     *
     * @return array Empty for snapshots written without order inputs (Express Checkout, never used by webhooks).
     */
    public static function orderInputsOf(array $snapshot): array
    {
        return (array)($snapshot[self::ORDER_INPUTS_KEY] ?? []);
    }

    /**
     * @param string $orderReference
     *
     * @return void
     */
    public function release(string $orderReference): void
    {
        if ($orderReference === '') {
            return;
        }

        $this->persister->delete(self::KEY_PREFIX . $orderReference);
    }
}
