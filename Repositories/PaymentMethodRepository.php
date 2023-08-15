<?php

namespace AdyenPayment\Repositories;

use Adyen\Core\BusinessLogic\DataAccess\Payment\Contracts\PaymentsRepository;

/**
 * Class PaymentMethodRepository
 *
 * @package AdyenPayment\Repositories
 */
class PaymentMethodRepository extends BaseRepositoryWithConditionalDeletes implements PaymentsRepository
{
    /**
     * Fully qualified name of this class.
     */
    public const THIS_CLASS_NAME = __CLASS__;
}
