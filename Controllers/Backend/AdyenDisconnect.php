<?php

use Adyen\Core\BusinessLogic\AdminAPI\AdminAPI;
use Adyen\Core\BusinessLogic\AdminAPI\Integration\Response\StateResponse;
use Adyen\Core\BusinessLogic\Domain\Multistore\StoreContext;
use Adyen\Core\BusinessLogic\Domain\Payment\Repositories\PaymentMethodConfigRepository;
use Adyen\Core\Infrastructure\ServiceRegister;
use AdyenPayment\Components\Integration\FileService;
use AdyenPayment\Controllers\Common\AjaxResponseSetter;

class Shopware_Controllers_Backend_AdyenDisconnect extends Enlight_Controller_Action
{
    use AjaxResponseSetter {
        AjaxResponseSetter::preDispatch as protected ajaxResponseSetterPreDispatch;
    }

    /**
     * @var FileService
     */
    private $fileService;

    /**
     * @return void
     * @throws Exception
     */
    public function preDispatch(): void
    {
        $this->ajaxResponseSetterPreDispatch();
        $this->fileService = $this->get(FileService::class);
    }

    /**
     * @throws Exception
     */
    public function disconnectAction(): void
    {
        $storeId = $this->Request()->get('storeId');

        $response = AdminAPI::get()->integration($storeId)->getState();
        if ($response->toArray() === StateResponse::onboarding()->toArray()) {
            $this->returnAPIResponse($response);

            return;
        }
        StoreContext::doWithStore($storeId, function () {
            $this->removeImages();
        });
        $result = AdminAPI::get()->disconnect($storeId)->disconnect();

        $this->returnAPIResponse($result);
    }

    /**
     * @return void
     *
     * @throws Exception
     */
    private function removeImages(): void
    {
        $storeId = StoreContext::getInstance()->getStoreId();
        $this->fileService->delete('adyen-giving-logo-store-' . $storeId);
        $this->fileService->delete('adyen-giving-background-store-' . $storeId);

        foreach ($this->getPaymentMethodConfigRepository()->getConfiguredPaymentMethods() as $method) {
            $this->fileService->delete($method->getMethodId() . '_store_' . $storeId);
        }
    }

    /**
     * @return PaymentMethodConfigRepository
     */
    private function getPaymentMethodConfigRepository(): PaymentMethodConfigRepository
    {
        return ServiceRegister::getService(PaymentMethodConfigRepository::class);
    }
}
