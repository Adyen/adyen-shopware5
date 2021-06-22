<?php

declare(strict_types=1);

namespace AdyenPayment\Doctrine\Writer;

use AdyenPayment\AdyenPayment;
use AdyenPayment\Dbal\Provider\Payment\PaymentMeanProviderInterface;
use AdyenPayment\Import\PaymentAttributeUpdaterInterface;
use AdyenPayment\Models\Payment\PaymentFactoryInterface;
use AdyenPayment\Models\Payment\PaymentMethod;
use AdyenPayment\Models\PaymentMethod\ImportResult;
use Doctrine\Common\Cache\Cache;
use Shopware\Bundle\AttributeBundle\Service\CrudServiceInterface;
use Shopware\Bundle\AttributeBundle\Service\DataPersisterInterface;
use Shopware\Bundle\AttributeBundle\Service\TypeMapping;
use Shopware\Components\Model\ModelManager;
use Shopware\Models\Payment\Payment;
use Shopware\Models\Shop\Shop;

final class PaymentMethodWriter implements PaymentMethodWriterInterface
{
    /** @var ModelManager */
    private $entityManager;
    /** @var PaymentMeanProviderInterface */
    private $paymentMeanProvider;
    /** @var DataPersisterInterface */
    private $dataPersister;
    /** @var PaymentFactoryInterface */
    private $paymentFactory;
    /** @var PaymentAttributeUpdaterInterface */
    private $paymentAttributeUpdater;

    public function __construct(
        ModelManager $entityManager,
        PaymentMeanProviderInterface $paymentMeanProvider,
        DataPersisterInterface $dataPersister,
        PaymentFactoryInterface $paymentFactory,
        PaymentAttributeUpdaterInterface $paymentAttributeUpdater
    ) {
        $this->entityManager = $entityManager;
        $this->paymentMeanProvider = $paymentMeanProvider;
        $this->dataPersister = $dataPersister;
        $this->paymentFactory = $paymentFactory;
        $this->paymentAttributeUpdater = $paymentAttributeUpdater;
    }

    public function __invoke(
        PaymentMethod $adyenPaymentMethod,
        Shop $shop
    ): ImportResult {
        $payment = $this->write($adyenPaymentMethod, $shop);

        $this->storeAdyenPaymentMethodType(
            $payment->getId(),
            $adyenPaymentMethod->getType()
        );

        return ImportResult::success($shop, $adyenPaymentMethod);
    }

    private function write(PaymentMethod $adyenPaymentMethod, Shop $shop): Payment
    {
        $swPayment = $this->paymentMeanProvider->provideByAdyenType($adyenPaymentMethod->getType());

        $payment = null !== $swPayment
            ? $this->paymentFactory->updateFromAdyen($swPayment, $adyenPaymentMethod, $shop)
            : $this->paymentFactory->createFromAdyen($adyenPaymentMethod, $shop);

        $this->entityManager->persist($payment);
        $this->entityManager->flush();

        return $payment;
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

        // update read only "false" to allow model changes
        $this->paymentAttributeUpdater->setReadonlyOnAdyenTypePaymentAttribute(false);

        $this->dataPersister->persist(
            $data,
            "s_core_paymentmeans_attributes",
            $paymentMeanId
        );

        $this->paymentAttributeUpdater->setReadonlyOnAdyenTypePaymentAttribute(true);
    }
}