<?php

declare(strict_types=1);

namespace AdyenPayment\Doctrine\Writer;

use AdyenPayment\AdyenPayment;
use AdyenPayment\Dbal\PaymentAttributes;
use AdyenPayment\Models\Payment\PaymentMethod;
use AdyenPayment\Models\PaymentMethod\ImportResult;
use Doctrine\Common\Cache\Cache;
use Doctrine\Common\Collections\ArrayCollection;
use Shopware\Bundle\AttributeBundle\Service\CrudServiceInterface;
use Shopware\Bundle\AttributeBundle\Service\DataPersisterInterface;
use Shopware\Bundle\AttributeBundle\Service\TypeMapping;
use Shopware\Components\Model\ModelManager;
use Shopware\Components\Model\ModelRepository;
use Shopware\Models\Payment\Payment;
use Shopware\Models\Shop\Shop;

final class PaymentMethodWriter
{
    /** @var ModelManager */
    private $entityManager;
    /** @var PaymentAttributes */
    private $paymentAttributes;
    /** @var ModelRepository */
    private $paymentRepository;
    /** @var ModelRepository */
    private $countryRepository;
    /** @var DataPersisterInterface */
    private $dataPersister;
    /** @var CrudServiceInterface */
    private $crudService;

    public function __construct(
        ModelManager $entityManager,
        PaymentAttributes $paymentAttributes,
        ModelRepository $paymentRepository,
        ModelRepository $countryRepository,
        DataPersisterInterface $dataPersister,
        CrudServiceInterface $crudService
    )
    {
        $this->entityManager = $entityManager;
        $this->paymentAttributes = $paymentAttributes;
        $this->paymentRepository = $paymentRepository;
        $this->countryRepository = $countryRepository;
        $this->dataPersister = $dataPersister;
        $this->crudService = $crudService;
    }

    public function saveAsShopwarePaymentMethod(
        PaymentMethod $adyenPaymentMethod,
        Shop $shop
    ): ImportResult {

        $shops = new ArrayCollection([$shop]);
        $countries = $this->fetchCountryList();

        $existingPaymentAttribute = $this->paymentAttributes->fetchByAdyenType($adyenPaymentMethod->getType());
        $existingPaymentMethod = null;
        if (count($existingPaymentAttribute) !== 0) {
            $existingPaymentMethod = $this->paymentRepository->findOneBy([
                'id' => $existingPaymentAttribute['paymentmeanID']
            ]);
        }

        if ($existingPaymentMethod) {
            $existingPaymentMethod = $existingPaymentMethod->updateFromAdyenPaymentMethod(
                $adyenPaymentMethod,
                $shops,
                $countries
            );
            $this->storeAdyenPaymentMethodType(
                $existingPaymentMethod->getId(),
                $adyenPaymentMethod->getType()
            );

            return ImportResult::success($shop, $adyenPaymentMethod, true);
        }

        $shopwarePaymentModel = Payment::createFromAdyenPaymentMethod($adyenPaymentMethod, $shops, $countries);

        $this->entityManager->persist($shopwarePaymentModel);
        $this->entityManager->flush();

        $this->storeAdyenPaymentMethodType(
            $shopwarePaymentModel->getId(),
            $adyenPaymentMethod->getType()
        );

        return ImportResult::success($shop, $adyenPaymentMethod, false);
    }

    private function fetchCountryList(): ArrayCollection
    {
        return new ArrayCollection($this->countryRepository->findAll());
    }

    private function storeAdyenPaymentMethodType(
        int $paymentMeanId,
        string $adyenPaymentMethodType

    ) {
        $data = [
            '_table' => "s_core_paymentmeans_attributes",
            '_foreignKey' => $paymentMeanId,
            AdyenPayment::ADYEN_PAYMENT_METHOD_LABEL => $adyenPaymentMethodType
        ];

        try {
            // We need to set the payment attribute to readOnly false,
            // otherwise it does not save the payment attribute.
            $this->setReadonlyOnAdyenTypePaymentAttribute(false);

            $this->dataPersister->persist(
                $data,
                "s_core_paymentmeans_attributes",
                $paymentMeanId
            );

            $this->setReadonlyOnAdyenTypePaymentAttribute(true);
        } catch (\Exception $exception) {
            throw new $exception;
        }

    }

    private function setReadonlyOnAdyenTypePaymentAttribute(bool $readOnly)
    {
        $this->crudService->update(
            's_core_paymentmeans_attributes',
            AdyenPayment::ADYEN_PAYMENT_METHOD_LABEL,
            TypeMapping::TYPE_STRING,
            [
                'displayInBackend' => true,
                'readonly' => $readOnly,
                'label' => 'Adyen payment type'
            ]
        );

        $this->rebuildPaymentAttributeModel();
    }

    private function rebuildPaymentAttributeModel()
    {
        /** @var Cache $metaDataCache */
        $metaDataCache = $this->entityManager->getConfiguration()->getMetadataCacheImpl();
        if ($metaDataCache) {
            $metaDataCache->deleteAll();
        }

        $this->entityManager->generateAttributeModels(['s_core_paymentmeans_attributes']);
    }
}