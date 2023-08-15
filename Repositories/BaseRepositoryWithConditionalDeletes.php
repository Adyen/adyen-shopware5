<?php

namespace AdyenPayment\Repositories;

use Adyen\Core\BusinessLogic\DataAccess\Interfaces\ConditionallyDeletes;
use Adyen\Core\Infrastructure\Logger\Logger;
use Adyen\Core\Infrastructure\ORM\QueryFilter\QueryFilter;
use Adyen\Core\Infrastructure\ORM\Utility\IndexHelper;
use Exception;

/**
 * Class BaseRepositoryWithConditionalDeletes
 *
 * @package AdyenPayment\Repositories
 */
class BaseRepositoryWithConditionalDeletes extends BaseRepository implements ConditionallyDeletes
{
    /**
     * Fully qualified name of this class.
     */
    public const THIS_CLASS_NAME = __CLASS__;
    /**
     * @inheritDoc
     */
    public function deleteWhere(QueryFilter $queryFilter = null): void
    {
        try {
            $entity = new $this->entityClass;
            $type = $entity->getConfig()->getType();
            $indexMap = IndexHelper::mapFieldsToIndexes($entity);

            $query = $this->entityManager->createQueryBuilder();
            $alias = 'p';
            $query->delete()
                ->from(static::$doctrineModel, $alias)
                ->where("$alias.type = :type")
                ->setParameter('type', $type);

            $groups = $queryFilter ? $this->buildConditionGroups($queryFilter, $indexMap) : [];
            $queryParts = $this->getQueryParts($groups, $indexMap, $alias);

            $where = $this->generateWhereStatement($queryParts);
            if (!empty($where)) {
                $query->andWhere($where);
            }

            $query->getQuery()->execute();
        } catch (Exception $e) {
            Logger::logError('Delete where failed with error ' . $e->getMessage());
        }
    }
}
