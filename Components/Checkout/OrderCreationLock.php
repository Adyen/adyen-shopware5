<?php

namespace AdyenPayment\Components\Checkout;

use Adyen\Core\Infrastructure\Logger\Logger;
use AdyenPayment\Repositories\Wrapper\OrderRepository;
use Doctrine\DBAL\Connection;
use Exception;

/**
 * Class OrderCreationLock
 *
 * Serialises order creation per order reference between the checkout and the webhook.
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
     * @var OrderRepository
     */
    private $orderRepository;

    /**
     * @param Connection $connection
     * @param OrderRepository $orderRepository
     */
    public function __construct(Connection $connection, OrderRepository $orderRepository)
    {
        $this->connection = $connection;
        $this->orderRepository = $orderRepository;
    }

    /**
     * Same lookup as OrderService::orderExists(), but regardless of the shop.
     *
     * @param string $orderReference
     *
     * @return bool
     */
    public function orderExists(string $orderReference): bool
    {
        return $orderReference !== '' && $this->orderRepository->getOrderByTemporaryId($orderReference) !== null;
    }

    /**
     * Runs the callback under the lock for the reference. If the lock cannot be taken, the callback still runs
     * when $runWithoutLock is true (checkout), otherwise it is skipped (webhook, which is retried).
     *
     * @param string $orderReference
     * @param callable $callback
     * @param bool $runWithoutLock
     *
     * @return mixed Null when the callback was skipped.
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
