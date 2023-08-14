<?php

namespace AdyenPayment\Components\Integration\PaymentProcessors;

use Adyen\Core\BusinessLogic\Domain\Checkout\PaymentRequest\Factory\PaymentRequestBuilder;
use Adyen\Core\BusinessLogic\Domain\Checkout\PaymentRequest\Models\StartTransactionRequestContext;
use Adyen\Core\BusinessLogic\Domain\Integration\Processors\BirthdayProcessor as BaseBirthdayProcessor;

/**
 * Class BirthdayProcessor
 *
 * @package AdyenPayment\Components\Integration\PaymentProcessors
 */
class BirthdayProcessor implements BaseBirthdayProcessor
{
    public function process(PaymentRequestBuilder $builder, StartTransactionRequestContext $context): void
    {
        $stateDataBirthday = $context->getStateData()->get('dateOfBirth');

        if (!empty($stateDataBirthday)) {
            return;
        }

        $user = $context->getCheckoutSession()->get('user');

        if (empty($user) || !isset($user['additional']['user']['birthday'])) {
            return;
        }

        $builder->setDateOfBirth($user['additional']['user']['birthday']);
    }
}
