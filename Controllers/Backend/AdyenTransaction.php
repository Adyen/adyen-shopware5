<?php

use Adyen\Core\BusinessLogic\Domain\Checkout\PaymentRequest\Exceptions\CurrencyMismatchException;
use Adyen\Core\BusinessLogic\Domain\Multistore\StoreContext;
use Adyen\Core\BusinessLogic\Domain\TransactionHistory\Services\TransactionDetailsService;
use Adyen\Core\Infrastructure\ServiceRegister;
use AdyenPayment\Controllers\Common\AjaxResponseSetter;
use AdyenPayment\Models\TransactionLogEntity;
use AdyenPayment\Repositories\Wrapper\OrderRepository;

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
        $orderId = $this->Request()->get('id');
        $temporaryId = $this->Request()->get('temporaryId');
        $order = $orderId ?
            $this->getOrderRepository()->getOrderById($orderId) :
            $this->getOrderRepository()->getOrderByTemporaryId((string)$temporaryId);
        $merchantReference = $order->getTemporaryId();
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

    /**
     * @return OrderRepository
     */
    private function getOrderRepository(): OrderRepository
    {
        return Shopware()->Container()->get(OrderRepository::class);
    }
}
