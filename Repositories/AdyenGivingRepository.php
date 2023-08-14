<?php

namespace AdyenPayment\Repositories;

use Adyen\Core\BusinessLogic\DataAccess\AdyenGiving\Contracts\AdyenGivingRepository as BaseAdyenGivingRepository;

/**
 * Class AdyenGivingRepository
 *
 * @package AdyenPayment\Repositories
 */
class AdyenGivingRepository extends BaseRepositoryWithConditionalDeletes implements BaseAdyenGivingRepository
{
    /**
     * Fully qualified name of this class.
     */
    public const THIS_CLASS_NAME = __CLASS__;
}
