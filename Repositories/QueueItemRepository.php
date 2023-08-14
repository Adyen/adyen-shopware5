<?php

namespace AdyenPayment\Repositories;

use Adyen\Core\Infrastructure\ORM\Configuration\Index;
use Adyen\Core\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException;
use Adyen\Core\BusinessLogic\ORM\Interfaces\QueueItemRepository as BaseItemRepository;
use Adyen\Core\Infrastructure\ORM\QueryFilter\Operators;
use Adyen\Core\Infrastructure\ORM\QueryFilter\QueryFilter;
use Adyen\Core\Infrastructure\ORM\Utility\IndexHelper;
use Adyen\Core\Infrastructure\TaskExecution\Exceptions\QueueItemSaveException;
use Adyen\Core\Infrastructure\TaskExecution\QueueItem;
use AdyenPayment\Models\QueueEntity;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\OptimisticLockException;
use Exception;

/**
 * Class QueueItemRepository
 *
 * @package AdyenPayment\Repositories
 */
class QueueItemRepository extends BaseRepositoryWithConditionalDeletes implements BaseItemRepository
{
    /**
     * Fully qualified name of this class.
     */
    public const THIS_CLASS_NAME = __CLASS__;

    protected static $doctrineModel = QueueEntity::class;

    /**
     * Finds list of earliest queued queue items per queue. Following list of criteria for searching must be satisfied:
     *      - Queue must be without already running queue items
     *      - For one queue only one (oldest queued) item should be returned
     *
     * @param int $priority Queue item priority.
     * @param int $limit Result set limit. By default max 10 earliest queue items will be returned
     *
     * @return QueueItem[] Found queue item list
     */
    public function findOldestQueuedItems($priority, $limit = 10)
    {
        $result = [];

        try {
            /** @var Connection $connection */
            $connection = Shopware()->Container()->get('dbal_connection');

            $ids = $this->getQueueIdsForExecution($priority, $limit);
            $rawItems = $connection->createQueryBuilder()
                ->select('queue.id', 'queue.data')
                ->from($this->getDbName(), 'queue')
                ->where('id IN(:ids)')
                ->setParameter('ids', $ids, Connection::PARAM_INT_ARRAY)
                ->orderBy('queue.id')
                ->execute()
                ->fetchAll();

            $result = $this->inflateQueueItems(!empty($rawItems) ? $rawItems : []);
        } catch (Exception $e) {
            // In case of database exception return empty result set.
        }

        return $result;
    }

    /**
     * Creates or updates given queue item. If queue item id is not set, new queue item will be created otherwise
     * update will be performed.
     *
     * @param QueueItem $queueItem Item to save
     * @param array $additionalWhere List of key/value pairs that must be satisfied upon saving queue item. Key is
     *  queue item property and value is condition value for that property. Example for MySql storage:
     *  $storage->save($queueItem, array('status' => 'queued')) should produce query
     *  UPDATE queue_storage_table SET .... WHERE .... AND status => 'queued'
     *
     * @return int Id of saved queue item
     *
     * @throws OptimisticLockException
     * @throws QueryFilterInvalidParamException
     * @throws QueueItemSaveException if queue item could not be saved
     */
    public function saveWithCondition(QueueItem $queueItem, array $additionalWhere = array()): int
    {
        if ($queueItem->getId()) {
            $this->updateQueueItem($queueItem, $additionalWhere);

            return $queueItem->getId();
        }

        return $this->save($queueItem);
    }

    public function batchStatusUpdate(array $ids, $status)
    {
        /** @var Connection $connection */
        $connection = Shopware()->Container()->get('dbal_connection');

        $index = $this->getColumnIndexMap();
        $statusColumn = 'index_' . $index['status'];

        $connection->createQueryBuilder()
            ->update($this->getDbName(), 'queue')
            ->set("queue.$statusColumn", ':status')
            ->where('id IN(:ids)')
            ->setParameter(':status', $status)
            ->setParameter('ids', $ids, Connection::PARAM_INT_ARRAY)
            ->execute();
    }

    /**
     * Retrieves queue item ids.
     *
     * @param int $priority
     * @param int $limit
     *
     * @return array
     */
    protected function getQueueIdsForExecution(int $priority, int $limit): array
    {
        /** @var Connection $connection */
        $connection = Shopware()->Container()->get('dbal_connection');

        $index = $this->getColumnIndexMap();
        $nameColumn = 'index_' . $index['queueName'];
        $statusColumn = 'index_' . $index['status'];
        $priorityColumn = 'index_' . $index['priority'];
        $queuedStatus = QueueItem::QUEUED;
        $inProgressStatus = QueueItem::IN_PROGRESS;

        $runningQueueNames = $connection->createQueryBuilder()
            ->select("DISTINCT $nameColumn")
            ->from($this->getDbName(), 'queue')
            ->where("queue.$statusColumn = :status")
            ->setParameter(':status', $inProgressStatus)
            ->execute()
            ->fetchAll(\PDO::FETCH_COLUMN);

        $query = $connection->createQueryBuilder()
            ->select('MIN(queue.id) AS id')
            ->from($this->getDbName(), 'queue')
            ->where("queue.$statusColumn = :status")
            ->andWhere("queue.$priorityColumn = :priority")
            ->setParameter(':status', $queuedStatus)
            ->setParameter(':priority', IndexHelper::castFieldValue($priority, Index::INTEGER))
            ->groupBy("queue.$nameColumn");

        if (!empty($runningQueueNames)) {
            $query
                ->andWhere("queue.$nameColumn NOT IN(:names)")
                ->setParameter(':names', $runningQueueNames, Connection::PARAM_STR_ARRAY);
        }

        $result = $query->execute()->fetchAll(\PDO::FETCH_COLUMN);
        sort($result);

        return array_slice($result, 0, $limit);
    }

    /**
     * Updates queue item.
     *
     * @param QueueItem $queueItem
     * @param array $additionalWhere
     *
     *
     * @throws QueryFilterInvalidParamException
     * @throws QueueItemSaveException
     */
    protected function updateQueueItem(QueueItem $queueItem, array $additionalWhere): void
    {
        $filter = new QueryFilter();
        $filter->where('id', Operators::EQUALS, $queueItem->getId());

        foreach ($additionalWhere as $name => $value) {
            if ($value === null) {
                $filter->where($name, Operators::NULL);
            } else {
                $filter->where($name, Operators::EQUALS, $value);
            }
        }

        /** @var QueueItem $item */
        $item = $this->selectOne($filter);
        if ($item === null) {
            throw new QueueItemSaveException("Cannot update queue item with id {$queueItem->getId()}.");
        }

        $this->update($queueItem);
    }

    /**
     * Retrieves index column map.
     *
     * @return array
     */
    protected function getColumnIndexMap(): array
    {
        $queueItem = new QueueItem();

        return IndexHelper::mapFieldsToIndexes($queueItem);
    }

    /**
     * Retrieves db_name for DBAL.
     *
     * @return string
     */
    protected function getDbName(): string
    {
        return 's_plugin_adyen_queue';
    }

    /**
     * Inflates queue items.
     *
     * @param array $rawItems
     *
     * @return array
     */
    protected function inflateQueueItems(array $rawItems = []): array
    {
        $result = [];
        foreach ($rawItems as $rawItem) {
            $item = new QueueItem();
            $item->inflate(json_decode($rawItem['data'], true));
            $item->setId((int)$rawItem['id']);
            $result[] = $item;
        }

        return $result;
    }
}
