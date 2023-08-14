<?php

namespace AdyenPayment\Components\Integration;

use Adyen\Core\BusinessLogic\Domain\Checkout\PaymentRequest\Models\PaymentMethodCode;
use Adyen\Core\BusinessLogic\Domain\Integration\Payment\ShopPaymentService;
use Adyen\Core\BusinessLogic\Domain\Multistore\StoreContext;
use Adyen\Core\BusinessLogic\Domain\Payment\Models\MethodAdditionalData\Oney;
use Adyen\Core\BusinessLogic\Domain\Payment\Models\PaymentMethod;
use Adyen\Core\BusinessLogic\Domain\Payment\Repositories\PaymentMethodConfigRepository;
use Adyen\Core\BusinessLogic\Domain\Payment\Services\PaymentService;
use Adyen\Core\Infrastructure\Exceptions\BaseException;
use Adyen\Core\Infrastructure\ServiceRegister;
use AdyenPayment\AdyenPayment;
use AdyenPayment\Exceptions\PaymentMeanDoesNotExistException;
use AdyenPayment\Exceptions\StoreDoesNotExistException;
use AdyenPayment\Repositories\Wrapper\StoreRepository;
use Adyen\Core\BusinessLogic\Domain\Integration\Store\StoreService as StoreServiceInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Exception;
use Shopware\Components\Model\ModelManager;
use Shopware\Components\Plugin\PaymentInstaller;
use Shopware\Models\Country\Country;
use Shopware\Models\Payment\Payment;
use Shopware\Models\Shop\Shop as ShopwareStore;

/**
 * Class PaymentMethodService
 *
 * @package AdyenPayment\BusinessService
 */
class PaymentMethodService implements ShopPaymentService
{
    public const ADYEN_NAME_PREFIX = 'adyen_';

    /**
     * @var StoreContext
     */
    private $storeContext;
    /**
     * @var ModelManager
     */
    private $entityManager;
    /**
     * @var StoreRepository
     */
    private $storeRepository;
    /**
     * @var PaymentInstaller
     */
    private $paymentInstaller;
    /**
     * @var FileService
     */
    private $fileService;
    /**
     * @var PaymentMethodConfigRepository
     */
    private $paymentRepository;

    /**
     * @var StoreService
     */
    private $storeService;

    /**
     * @param StoreContext $storeContext
     * @param ModelManager $entityManager
     * @param StoreRepository $storeRepository
     * @param PaymentInstaller $paymentInstaller
     * @param FileService $fileService
     * @param PaymentMethodConfigRepository $paymentRepository
     * @param StoreServiceInterface $storeService
     */
    public function __construct(
        StoreContext $storeContext,
        ModelManager $entityManager,
        StoreRepository $storeRepository,
        PaymentInstaller $paymentInstaller,
        FileService $fileService,
        PaymentMethodConfigRepository $paymentRepository,
        StoreServiceInterface $storeService
    ) {
        $this->storeContext = $storeContext;
        $this->entityManager = $entityManager;
        $this->storeRepository = $storeRepository;
        $this->paymentInstaller = $paymentInstaller;
        $this->fileService = $fileService;
        $this->paymentRepository = $paymentRepository;
        $this->storeService = $storeService;
    }

    /**
     * Creates new payment method in Shopware.
     *
     * @param PaymentMethod $method
     *
     * @return void
     *
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws StoreDoesNotExistException
     */
    public function createPaymentMethod(PaymentMethod $method): void
    {
        $store = $this->getCurrentStore();

        $this->savePaymentMean($method, $store);
    }

    /**
     * @inheritDoc
     *
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws PaymentMeanDoesNotExistException
     * @throws StoreDoesNotExistException
     * @throws Exception
     */
    public function updatePaymentMethod(PaymentMethod $method): void
    {
        $store = $this->getCurrentStore();
        $payment = $this->getPaymentMeanByName(self::ADYEN_NAME_PREFIX . $method->getCode());

        if (!$payment && $method->getCode() !== (string)PaymentMethodCode::oney()) {
            throw new PaymentMeanDoesNotExistException(
                'Payment mean with name ' . self::ADYEN_NAME_PREFIX
                . $method->getCode() . ' does not exist.'
            );
        }

        $this->savePaymentMean($method, $store);
    }

    /**
     * @inheritDoc
     *
     * @throws BaseException
     * @throws Exception
     */
    public function deletePaymentMethod(string $methodId): void
    {
        $method = $this->getPaymentService()->getPaymentMethodById($methodId);

        if ($method === null) {
            throw new BaseException('Payment method with id ' . $methodId . 'does not exist.');
        }

        if ($method->getCode() === (string)PaymentMethodCode::oney()) {
            $this->removeOneyMethods($method);

            return;
        }

        $this->disablePaymentMean(self::ADYEN_NAME_PREFIX . $method->getCode());
    }

    /**
     * @return void
     *
     * @throws OptimisticLockException
     * @throws \Doctrine\ORM\Exception\ORMException
     * @throws Exception
     */
    public function deleteAllPaymentMethods(): void
    {
        $paymentMeans = $this->getConfiguredPaymentMeans();

        if (empty($paymentMeans)) {
            return;
        }

        foreach ($paymentMeans as $paymentMean) {
            $this->disableMean($paymentMean);
        }

        $this->entityManager->flush();
    }

    /**
     * @return void
     *
     * @throws OptimisticLockException
     * @throws StoreDoesNotExistException
     * @throws \Doctrine\ORM\Exception\ORMException
     * @throws Exception
     */
    public function deletePaymentMethodsForAllStores(): void
    {
        $paymentMeans = $this->getConfiguredPaymentMeans();

        if (empty($paymentMeans)) {
            return;
        }

        foreach ($paymentMeans as $paymentMean) {
            $this->deleteMean($paymentMean);
        }

        $this->entityManager->flush();
    }

    /**
     * @return void
     *
     * @throws OptimisticLockException
     * @throws \Doctrine\ORM\Exception\ORMException
     * @throws Exception
     */
    public function enableAllPaymentMethods(): void
    {
        $paymentMeans = $this->getConfiguredPaymentMeans();

        if (empty($paymentMeans)) {
            return;
        }

        foreach ($paymentMeans as $paymentMean) {
            $paymentMean->setHide(false);
            $paymentMean->setActive(true);
            $this->entityManager->persist($paymentMean);
        }

        $this->entityManager->flush();
    }

    /**
     * @return array|Payment[]
     *
     * @throws Exception
     */
    private function getConfiguredPaymentMeans(): array
    {
        $methods = $this->paymentRepository->getConfiguredPaymentMethodsForAllShops();

        if (StoreContext::getInstance()->getStoreId()) {
            $methods = $this->paymentRepository->getConfiguredPaymentMethods();
        }

        if (empty($methods)) {
            return [];
        }

        $names = [];

        foreach ($methods as $method) {
            $names[] = self::ADYEN_NAME_PREFIX . $method->getCode();

            if ($method->getCode() === (string)PaymentMethodCode::oney()) {
                /** @var Oney $additionalData */
                $additionalData = $method->getAdditionalData();
                $installments = $additionalData->getSupportedInstallments();

                foreach ($installments as $installment) {
                    $names[] = self::ADYEN_NAME_PREFIX . 'facilypay_' . $installment . 'x';
                }
            }
        }

        return $this->getPaymentMeansByName($names);
    }

    /**
     * @param string $name
     *
     * @return Payment|null
     */
    private function getPaymentMeanByName(string $name): ?Payment
    {
        $repository = Shopware()->Models()->getRepository(Payment::class);
        $query = $repository->createQueryBuilder('paymentmeans');
        $query->where('paymentmeans.name = :name')
            ->setParameter('name', $name);

        $paymentMeans = $query->getQuery()->getResult();

        return $paymentMeans[0] ?? null;
    }

    /**
     * @param array $names
     *
     * @return Payment[]
     */
    private function getPaymentMeansByName(array $names): array
    {
        $repository = Shopware()->Models()->getRepository(Payment::class);
        $query = $repository->createQueryBuilder('paymentmeans');
        $query->where('paymentmeans.name in (:names)')
            ->setParameter('names', $names, Connection::PARAM_STR_ARRAY);

        return $query->getQuery()->getResult();
    }

    /**
     * @param PaymentMethod $method
     * @param ShopwareStore $store
     *
     * @return void
     *
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws Exception
     */
    private function savePaymentMean(PaymentMethod $method, ShopwareStore $store): void
    {
        if ($method->getCode() === (string)PaymentMethodCode::oney()) {
            $this->saveOneyMeans($method, $store);

            return;
        }

        $payment = $this->getPaymentMeanByName(self::ADYEN_NAME_PREFIX . $method->getCode());

        $shops = array_merge([$store], $this->storeRepository->getShopwareLanguageShops([$store->getId()]));

        if ($payment) {
            /** @var ShopwareStore $enabledShop */
            foreach ($payment->getShops()->toArray() as $enabledShop) {
                foreach ($shops as $shop) {
                    if ($shop->getId() === $enabledShop->getId()) {
                        continue 2;
                    }
                }

                $shops[] = $enabledShop;
            }
        }

        $this->paymentInstaller->createOrUpdate(
            AdyenPayment::NAME,
            [
                'name' => self::ADYEN_NAME_PREFIX . $method->getCode(),
                'description' => $method->getName(),
                'additionalDescription' => $method->getDescription(),
                'active' => true,
                'esdActive' => true,
                'hide' => false,
                'position' => $this->getPosition($method),
                'action' => 'AdyenPaymentProcess',
                'source' => AdyenPayment::PAYMENT_METHOD_SOURCE,
                'debitPercent' => $method->getPercentSurcharge() ?? 0,
                'surcharge' => $method->getFixedSurcharge(),
                'countries' => $this->getCountries(),
                'shops' => new ArrayCollection($shops),
            ]
        );
    }

    /**
     * @param PaymentMethod $method
     * @param ShopwareStore $store
     *
     * @return void
     *
     * @throws Exception
     */
    private function saveOneyMeans(PaymentMethod $method, ShopwareStore $store): void
    {
        /** @var Oney $additionalData */
        $additionalData = $method->getAdditionalData();
        $installments = $additionalData->getSupportedInstallments();

        foreach ($installments as $installment) {
            $payment = $this->getPaymentMeanByName(self::ADYEN_NAME_PREFIX . 'facilypay_' . $installment . 'x');

            $shops = array_merge([$store], $this->storeRepository->getShopwareLanguageShops([$store->getId()]));

            if ($payment) {
                /** @var ShopwareStore $enabledShop */
                foreach ($payment->getShops()->toArray() as $enabledShop) {
                    foreach ($shops as $shop) {
                        if ($shop->getId() === $enabledShop->getId()) {
                            continue 2;
                        }
                    }

                    $shops[] = $enabledShop;
                }
            }

            $this->paymentInstaller->createOrUpdate(
                AdyenPayment::NAME,
                [
                    'name' => self::ADYEN_NAME_PREFIX . 'facilypay_' . $installment . 'x',
                    'description' => $method->getName() . ' ' . $installment . 'X',
                    'additionalDescription' => $method->getDescription(),
                    'active' => true,
                    'esdActive' => true,
                    'hide' => false,
                    'position' => $this->getPosition($method),
                    'action' => 'AdyenPaymentProcess',
                    'source' => AdyenPayment::PAYMENT_METHOD_SOURCE,
                    'debitPercent' => $method->getPercentSurcharge() ?? 0,
                    'surcharge' => $method->getFixedSurcharge(),
                    'countries' => $this->getCountries(),
                    'shops' => new ArrayCollection($shops),
                ]
            );
        }

        $this->disableOneyInstallments($method);
    }

    private function disableOneyInstallments(PaymentMethod $method)
    {
        $oneyMeans = $this->getOneyPaymentMeans();
        $enabledInstallments = $method->getAdditionalData()->getSupportedInstallments();

        foreach ($oneyMeans as $mean) {
            $installment = str_replace(self::ADYEN_NAME_PREFIX . 'facilypay_', '', $mean->getName());
            $installment = str_replace('x', '', $installment);

            if (!in_array($installment, $enabledInstallments)) {
                $name = self::ADYEN_NAME_PREFIX . 'facilypay_' . $installment . 'x';

                $this->disablePaymentMean($name);
            }
        }
    }

    /**
     * @return array
     */
    private function getOneyPaymentMeans(): array
    {
        $repository = Shopware()->Models()->getRepository(Payment::class);
        $query = $repository->createQueryBuilder('paymentmeans');
        $query->where('paymentmeans.name LIKE :name')
            ->setParameter('name', '%' . self::ADYEN_NAME_PREFIX . 'facilypay_%');

        $paymentMeans = $query->getQuery()->getResult();

        return $paymentMeans ?? [];
    }

    /**
     * @param PaymentMethod $method
     *
     * @return void
     *
     * @throws StoreDoesNotExistException
     * @throws \Doctrine\ORM\Exception\ORMException
     */
    private function removeOneyMethods(PaymentMethod $method): void
    {
        /** @var Oney $additionalData */
        $additionalData = $method->getAdditionalData();
        $installments = $additionalData->getSupportedInstallments();
        $this->fileService->delete($method->getMethodId());

        foreach ($installments as $installment) {
            $name = self::ADYEN_NAME_PREFIX . 'facilypay_' . $installment . 'x';

            $this->disablePaymentMean($name);
        }
    }

    /**
     * @param $name
     *
     * @return void
     *
     * @throws StoreDoesNotExistException
     * @throws \Doctrine\ORM\Exception\ORMException
     */
    private function disablePaymentMean($name): void
    {
        $payment = $this->getPaymentMeanByName($name);

        if (!$payment) {
            return;
        }

        $this->disableMean($payment);
    }

    /**
     * @param Payment $paymentMean
     *
     * @return void
     *
     * @throws OptimisticLockException
     * @throws StoreDoesNotExistException
     * @throws \Doctrine\ORM\Exception\ORMException
     */
    private function deleteMean(Payment $paymentMean)
    {
        $storesForRemoval = $this->getStoresForRemoval();
        $stores = $paymentMean->getShops();

        foreach ($storesForRemoval as $store) {
            $stores->removeElement($store);
        }

        if ($stores->isEmpty()) {
            $paymentMean->setActive(false);
        }

        $paymentMean->setShops($stores);
        $this->entityManager->persist($paymentMean);
        $this->entityManager->flush();
    }

    /**
     * @param Payment $paymentMean
     *
     * @return void
     *
     * @throws OptimisticLockException
     * @throws \Doctrine\ORM\Exception\ORMException
     */
    private function disableMean(Payment $paymentMean)
    {
        $stores = $paymentMean->getShops();

        foreach ($stores as $store) {
            if ($store->getId() . '' === StoreContext::getInstance()->getStoreId()) {
                $stores->removeElement($store);
            }
        }

        if ($stores->isEmpty()) {
            $paymentMean->setActive(false);
        }

        $paymentMean->setShops($stores);
        $this->entityManager->persist($paymentMean);
        $this->entityManager->flush();
    }

    /**
     * @return ArrayCollection
     */
    private function getCountries(): ArrayCollection
    {
        $repository = Shopware()->Models()->getRepository(Country::class);
        $queryBuilder = $repository->createQueryBuilder('country');

        return new ArrayCollection($queryBuilder->getQuery()->getResult());
    }

    /**
     * @param PaymentMethod $method
     *
     * @return int
     *
     * @throws Exception
     */
    private function getPosition(PaymentMethod $method): int
    {
        $availableMethods = $this->getPaymentService()->getAvailableMethods();
        $position = 0;

        foreach ($availableMethods as $availableMethod) {
            if ($availableMethod->getCode() === $method->getCode()) {
                return $position;
            }

            $position++;
        }

        return 0;
    }

    /**
     * @return array
     */
    private function getStoresForRemoval(): array
    {
        $stores = $this->storeService->getConnectedStores();
        $shopwareStores = [];

        foreach ($stores as $store){
            $shopwareStores[] = $this->storeRepository->getStoreById($store);
        }

        return $shopwareStores;
    }

    /**
     * @return ShopwareStore
     *
     * @throws StoreDoesNotExistException
     */
    private function getCurrentStore(): ShopwareStore
    {
        $store = $this->storeRepository->getStoreById($this->storeContext->getStoreId());

        if (!$store) {
            throw new StoreDoesNotExistException(
                'Store with id ' . $this->storeContext->getStoreId()
                . ' does not exist.'
            );
        }

        return $store;
    }

    /**
     * @return PaymentService
     */
    private function getPaymentService(): PaymentService
    {
        return ServiceRegister::getService(PaymentService::class);
    }
}
