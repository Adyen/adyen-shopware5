<?php

namespace AdyenPayment\Components\Integration;

use Adyen\Core\BusinessLogic\DataAccess\Connection\Entities\ConnectionSettings;
use Adyen\Core\BusinessLogic\Domain\Integration\Store\StoreService as StoreServiceInterface;
use Adyen\Core\BusinessLogic\Domain\Stores\Exceptions\InvalidShopOrderDataException;
use Adyen\Core\BusinessLogic\Domain\Stores\Models\Store;
use Adyen\Core\BusinessLogic\Domain\Stores\Models\StoreOrderStatus;
use Adyen\Core\Infrastructure\Configuration\ConfigurationManager;
use Adyen\Core\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException;
use Adyen\Core\Infrastructure\ORM\Interfaces\RepositoryInterface;
use Adyen\Core\Infrastructure\ORM\QueryFilter\QueryFilter;
use Adyen\Core\Infrastructure\ServiceRegister;
use Adyen\Webhook\PaymentStates;
use AdyenPayment\Repositories\Wrapper\OrderRepository;
use AdyenPayment\Repositories\Wrapper\StoreRepository;
use Exception;
use Shopware\Components\StateTranslatorService;
use Shopware\Components\StateTranslatorServiceInterface;
use Shopware\Models\Order\Status;
use Shopware\Models\Shop\Shop as ShopwareStore;

/**
 * Class StoreService
 *
 * @package AdyenPayment\BusinessService
 */
class StoreService implements StoreServiceInterface
{
    /**
     * @var StoreRepository
     */
    private $storeRepository;

    /**
     * @var OrderRepository
     */
    private $orderRepository;
    /**
     * @var RepositoryInterface
     */
    private $connectionRepository;

    /**
     * @param StoreRepository $storeRepository
     * @param OrderRepository $orderRepository
     * @param RepositoryInterface $connectionRepository
     */
    public function __construct(
        StoreRepository $storeRepository,
        OrderRepository $orderRepository,
        RepositoryInterface $connectionRepository
    )
    {
        $this->storeRepository = $storeRepository;
        $this->orderRepository = $orderRepository;
        $this->connectionRepository = $connectionRepository;
    }

    /**
     * Returns store domain. If last character is /, delete it.
     *
     * @inheritDoc
     * @throws QueryFilterInvalidParamException
     */
    public function getStoreDomain(): string
    {
        $domain = Shopware()->Front()->Router()->assemble(['module' => 'frontend']);

        // only for test purposes
        $testHostname = $this->getConfigurationManager()->getConfigValue('testHostname');
        if($testHostname){
            $domain = str_replace(array('localhost', 'http://'), array($testHostname, 'https://'), $domain);
        }

        return rtrim($domain, '/');
    }

    /**
     * @inheritDoc
     *
     * @throws Exception
     */
    public function getStores(): array
    {
        return $this->transformStores($this->storeRepository->getShopwareSubShops());
    }

    /**
     * @inheritDoc
     *
     * @throws Exception
     */
    public function getDefaultStore(): ?Store
    {
        $defaultStore = $this->storeRepository->getShopwareDefaultShop();

        return $defaultStore ? $this->transformStore($defaultStore) : null;
    }

    /**
     * @inheritDoc
     *
     * @throws Exception
     */
    public function getStoreById(string $id): ?Store
    {
        $store = $this->storeRepository->getStoreById($id);

        return $store ? $this->transformStore($store) : null;
    }

    /**
     * @inheritDoc
     *
     * @throws InvalidShopOrderDataException
     */
    public function getStoreOrderStatuses(): array
    {
        return $this->transformStoreOrderStatuses($this->orderRepository->getOrderStatuses());
    }

    /**
     * @return array
     */
    public function getDefaultOrderStatusMapping(): array
    {
        return [
            PaymentStates::STATE_IN_PROGRESS => Status::PAYMENT_STATE_THE_PAYMENT_HAS_BEEN_ORDERED,
            PaymentStates::STATE_PENDING => Status::PAYMENT_STATE_OPEN,
            PaymentStates::STATE_PAID => Status::PAYMENT_STATE_COMPLETELY_PAID,
            PaymentStates::STATE_FAILED => Status::PAYMENT_STATE_THE_PROCESS_HAS_BEEN_CANCELLED,
            PaymentStates::STATE_CANCELLED => Status::PAYMENT_STATE_THE_PROCESS_HAS_BEEN_CANCELLED,
            PaymentStates::STATE_NEW => Status::PAYMENT_STATE_OPEN
        ];
    }

    /**
     * Retrieves connected stores ids.
     *
     * @return array
     */
    public function getConnectedStores(): array
    {
        /** @var ConnectionSettings[] $settings */
        $settings = $this->connectionRepository->select();
        $result = [];

        foreach ($settings as $item) {
            $result[] = $item->getStoreId();
        }

        return $result;
    }

    /**
     * @param array $shopwareStatuses
     *
     * @return array
     *
     * @throws InvalidShopOrderDataException
     */
    private function transformStoreOrderStatuses(array $shopwareStatuses): array
    {
        /** @var StateTranslatorServiceInterface $stateTranslator */
        $stateTranslator = Shopware()->Container()->get('shopware.components.state_translator');
        $storeOrderStatuses = [];

        foreach ($shopwareStatuses as $status) {
            $storeOrderStatuses[] = new StoreOrderStatus(
                (string)$status['id'],
                $stateTranslator->translateState(StateTranslatorService::STATE_PAYMENT, $status)['description']
            );
        }

        return $storeOrderStatuses;
    }

    /**
     * @param ShopwareStore $store
     *
     * @return Store
     *
     * @throws Exception
     */
    private function transformStore(ShopwareStore $store): Store
    {
        $config = clone Shopware()->Container()->get('config');
        $config->setShop($store);

        return new Store(
            (string)($store->getId() ?? ''),
            $store->getName() ?? '',
            $config->get('setOffline')
        );
    }

    /**
     * @param array $shopwareStores
     *
     * @return Store[]
     *
     * @throws Exception
     */
    private function transformStores(array $shopwareStores): array
    {
        $stores = [];
        $config = clone Shopware()->Container()->get('config');

        foreach ($shopwareStores as $shopwareStore) {
            $config->setShop($shopwareStore);
            $stores[] = new Store(
                $shopwareStore->getId() ?? '',
                $shopwareStore->getName() ?? '',
                $config->get('setOffline')
            );
        }

        return $stores;
    }

    /**
     * @return ConfigurationManager
     *
     */
    private function getConfigurationManager(): ConfigurationManager
    {
        return ServiceRegister::getService(ConfigurationManager::CLASS_NAME);
    }
}
