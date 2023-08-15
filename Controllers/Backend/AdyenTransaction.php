<?php

use Adyen\Core\BusinessLogic\Domain\Checkout\PaymentRequest\Exceptions\CurrencyMismatchException;
use Adyen\Core\BusinessLogic\Domain\Multistore\StoreContext;
use Adyen\Core\BusinessLogic\Domain\TransactionHistory\Services\TransactionDetailsService;
use Adyen\Core\Infrastructure\ServiceRegister;
use AdyenPayment\Controllers\Common\AjaxResponseSetter;
use AdyenPayment\Models\TransactionLogEntity;

class Shopware_Controllers_Backend_AdyenTransaction extends Shopware_Controllers_Backend_ExtJs
{
    use AjaxResponseSetter {
        AjaxResponseSetter::preDispatch as protected ajaxResponseSetterPreDispatch;
    }

    protected $model = TransactionLogEntity::class;
    protected $alias = 'transactionLog';

    /**
     * @return void
     * @throws Exception
     */
    public function preDispatch(): void
    {
        $this->ajaxResponseSetterPreDispatch();
    }

    /**
     * @return void
     *
     * @throws CurrencyMismatchException
     * @throws Exception
     */
    public function getAction(): void
    {
        $merchantReference = $this->Request()->get('temporaryId');
        $storeId = $this->Request()->get('storeId');
        $result = StoreContext::doWithStore(
            $storeId,
            [$this->getTransactionDetailsService($storeId), 'getTransactionDetails'],
            [$merchantReference, $storeId]
        );

        $this->Response()->setBody(json_encode($result));
    }

    /**
     * @param string $storeId
     *
     * @return TransactionDetailsService
     *
     * @throws Exception
     */
    private function getTransactionDetailsService(string $storeId): TransactionDetailsService
    {
        return StoreContext::doWithStore(
            $storeId,
            [ServiceRegister::getInstance(), 'getService'],
            [TransactionDetailsService::class]
        );
    }
}
