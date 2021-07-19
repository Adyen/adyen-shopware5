<?php

declare(strict_types=1);

namespace AdyenPayment\Doctrine\Writer;

use AdyenPayment\Dbal\Provider\Payment\PaymentMeanProviderInterface;
use AdyenPayment\Models\Payment\PaymentFactoryInterface;
use AdyenPayment\Models\Payment\PaymentMethod;
use AdyenPayment\Models\PaymentMethod\ImportResult;
use Shopware\Components\Model\ModelManager;
use Shopware\Models\Payment\Payment;
use Shopware\Models\Shop\Shop;

final class PaymentMethodWriter implements PaymentMethodWriterInterface
{
    /** @var ModelManager */
    private $entityManager;
    /** @var PaymentMeanProviderInterface */
    private $paymentMeanProvider;
    /** @var PaymentFactoryInterface */
    private $paymentFactory;
    /** @var PaymentAttributeWriterInterface */
    private $paymentAttributeWriter;

    public function __construct(
        ModelManager $entityManager,
        PaymentMeanProviderInterface $paymentMeanProvider,
        PaymentFactoryInterface $paymentFactory,
        PaymentAttributeWriterInterface $paymentAttributeWriter
    ) {
        $this->entityManager = $entityManager;
        $this->paymentMeanProvider = $paymentMeanProvider;
        $this->paymentFactory = $paymentFactory;
        $this->paymentAttributeWriter = $paymentAttributeWriter;
    }

    public function __invoke(
        PaymentMethod $adyenPaymentMethod,
        Shop $shop
    ): ImportResult {
        if ($adyenPaymentMethod->isStoredPayment()) {
            $payment = $this->writeStoredPaymentMethod($adyenPaymentMethod, $shop);

            $this->paymentAttributeWriter->storeAdyenPaymentMethodType(
                $payment->getId(),
                $adyenPaymentMethod
            );

            return ImportResult::success($shop, $adyenPaymentMethod);
        }

        $payment = $this->write($adyenPaymentMethod, $shop);

        $this->paymentAttributeWriter->storeAdyenPaymentMethodType(
            $payment->getId(),
            $adyenPaymentMethod
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

    private function writeStoredPaymentMethod(PaymentMethod $adyenStoredPaymentMethod, Shop $shop): Payment
    {
        $adyenStoredPaymentMethodId = $adyenStoredPaymentMethod->getStoredPaymentMethodId();
        $swPayment = $this->paymentMeanProvider->provideByAdyenStoredPaymentMethodId($adyenStoredPaymentMethodId);

        $payment = null !== $swPayment
            ? $this->paymentFactory->updateFromStoredAdyen($swPayment, $adyenStoredPaymentMethod, $shop)
            : $this->paymentFactory->createFromStoredAdyen($adyenStoredPaymentMethod, $shop);

        $this->entityManager->persist($payment);
        $this->entityManager->flush();

        return $payment;
    }
}
