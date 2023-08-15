<?php

namespace AdyenPayment\Components;

use Adyen\Core\BusinessLogic\Domain\Disconnect\Services\DisconnectService;
use Adyen\Core\BusinessLogic\Domain\Integration\Store\StoreService;
use Adyen\Core\BusinessLogic\Domain\Multistore\StoreContext;
use Adyen\Core\BusinessLogic\Domain\Payment\Exceptions\FailedToRetrievePaymentMethodsException;
use Adyen\Core\BusinessLogic\Domain\Payment\Repositories\PaymentMethodConfigRepository;
use Adyen\Core\Infrastructure\ServiceRegister;
use AdyenPayment\Components\Integration\FileService;
use Exception;

/**
 * Class UninstallService
 *
 * @package AdyenPayment\Components
 */
class UninstallService
{
    /**
     * @var StoreService
     */
    private $storeService;

    /**
     * @param StoreService $storeService
     */
    public function __construct(StoreService $storeService)
    {
        $this->storeService = $storeService;
    }

    /**
     * @throws Exception
     */
    public function uninstall(): void
    {
        $connectedStores = $this->storeService->getConnectedStores();

        foreach ($connectedStores as $store) {
            StoreContext::doWithStore(
                    $store,
                    function () {
                        $this->doUninstall();
                    }
            );
        }
    }

    /**
     * @return void
     *
     * @throws Exception
     */
    private function doUninstall(): void
    {
        try {
            $this->deleteImages();

            /** @var DisconnectService $disconnectService */
            $disconnectService = ServiceRegister::getService(DisconnectService::class);
            $disconnectService->removeWebhook();
            $disconnectService->disconnectIntegration();
        } catch (Exception $exception) {
            Shopware()->Container()->get('corelogger')->warning(
                    'Uninstallation for store '
                    . StoreContext::getInstance()->getStoreId() . ' failed: ' . $exception->getMessage()
            );
        }
    }

    /**
     * @throws FailedToRetrievePaymentMethodsException
     * @throws Exception
     */
    private function deleteImages(): void
    {
        $storeId = StoreContext::getInstance()->getStoreId();
        /** @var FileService $disconnectService */
        $fileService = ServiceRegister::getService(FileService::class);
        $fileService->delete('adyen-giving-logo-store-' . $storeId);
        $fileService->delete('adyen-giving-background-store-' . $storeId);

        /** @var PaymentMethodConfigRepository $paymentService */
        $paymentService = ServiceRegister::getService(PaymentMethodConfigRepository::class);
        $paymentMethods = $paymentService->getConfiguredPaymentMethods();
        foreach ($paymentMethods as $method) {
            $fileService->delete($method->getMethodId() . '_store_' . $storeId);
        }
    }
}
