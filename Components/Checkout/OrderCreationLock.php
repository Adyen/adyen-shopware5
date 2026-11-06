<?php

namespace AdyenPayment\Components\Checkout;

use Adyen\Core\Infrastructure\Logger\Logger;
use Doctrine\DBAL\Connection;
use Exception;

/**
 * Class OrderCreationLock
 *
 * Serialises order creation for one Adyen order reference across connections, so the checkout request and the
 * webhook task can never run their "does the order exist yet" check at the same time.
 *
 * @package AdyenPayment\Components\Checkout
 */
class OrderCreationLock
{
    private const PREFIX = 'adyen_order_';
    private const TIMEOUT_SECONDS = 30;

    /**
     * @var Connection
     */
    private $connection;

    /**
     * @param Connection $connection
     */
    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * The single "does the order exist" check both the checkout and the webhook run under the lock. The order
     * reference is unique per payment attempt, so it identifies the order on its own, without depending on the
     * PSP reference or on the customer being known to whichever side is asking.
     *
     * @param string $orderReference
     *
     * @return bool
     */
    public function orderExists(string $orderReference): bool
    {
        if ($orderReference === '') {
            return false;
        }

        $orderNumber = $this->connection->fetchColumn(
            'SELECT ordernumber FROM s_order WHERE temporaryID = :reference AND status != -1',
            [':reference' => $orderReference]
        );

        return !empty($orderNumber);
    }

    /**
     * Runs the callback while holding the lock for the given reference.
     *
     * When the lock cannot be taken, the caller decides. A checkout request has a shopper waiting and runs the
     * callback anyway, because failing the order outright is worse than the race. A webhook is retried, so it
     * declines instead and lets the next delivery try again.
     *
     * Without a reference there is nothing the two sides could race on, so the callback runs unlocked rather than
     * putting every such request behind one shared lock name.
     *
     * @param string $orderReference
     * @param callable $callback
     * @param bool $runWithoutLock
     *
     * @return mixed Null when the lock was refused and the caller asked not to run without it.
     *
     * @throws Exception
     */
    public function synchronized(string $orderReference, callable $callback, bool $runWithoutLock = true)
    {
        if ($orderReference === '') {
            return $callback();
        }

        $name = $this->name($orderReference);
        $acquired = $this->acquire($name);

        if (!$acquired && !$runWithoutLock) {
            Logger::logWarning(
                'Could not acquire the order creation lock for ' . $orderReference . '. Skipping this attempt.',
                'Integration'
            );

            return null;
        }

        try {
            return $callback();
        } finally {
            if ($acquired) {
                $this->release($name);
            }
        }
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    private function acquire(string $name): bool
    {
        try {
            return (int)$this->connection->fetchColumn(
                'SELECT GET_LOCK(?, ?)',
                [$name, self::TIMEOUT_SECONDS]
            ) === 1;
        } catch (Exception $exception) {
            return false;
        }
    }

    /**
     * @param string $name
     *
     * @return void
     */
    private function release(string $name): void
    {
        try {
            $this->connection->fetchColumn('SELECT RELEASE_LOCK(?)', [$name]);
        } catch (Exception $exception) {
            // The lock is released with the connection anyway.
        }
    }

    /**
     * @param string $orderReference
     *
     * @return string
     */
    private function name(string $orderReference): string
    {
        return substr(self::PREFIX . $orderReference, 0, 64);
    }
}
