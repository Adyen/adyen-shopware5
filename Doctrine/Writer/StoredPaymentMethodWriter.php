<?php

declare(strict_types=1);

namespace AdyenPayment\Doctrine\Writer;

use AdyenPayment\Dbal\Provider\Payment\PaymentMeanProviderInterface;
use AdyenPayment\Exceptions\ImportPaymentMethodException;
use AdyenPayment\Models\Payment\PaymentFactoryInterface;
use AdyenPayment\Models\Payment\PaymentMethod;
use AdyenPayment\Models\PaymentMethod\ImportResult;
use Shopware\Components\Model\ModelManager;
use Shopware\Models\Payment\Payment;
use Shopware\Models\Shop\Shop;

class StoredPaymentMethodWriter implements StoredPaymentMethodWriterInterface
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
    )
    {
        $this->entityManager = $entityManager;
        $this->paymentMeanProvider = $paymentMeanProvider;
        $this->paymentFactory = $paymentFactory;
        $this->paymentAttributeWriter = $paymentAttributeWriter;
    }

    public function __invoke(PaymentMethod $adyenStoredPaymentMethod, Shop $shop): ImportResult
    {
        $payment = $this->writeStoredPaymentMethod($adyenStoredPaymentMethod, $shop);

        if (null === $payment->getId()) {
            return ImportResult::fromException(
                $shop,
                $adyenStoredPaymentMethod,
                ImportPaymentMethodException::missingId()
            );
        }

        $this->paymentAttributeWriter->storeAdyenPaymentMethodType(
            $payment->getId(),
            $adyenStoredPaymentMethod
        );

        return ImportResult::success($shop, $adyenStoredPaymentMethod);
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
