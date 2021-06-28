<?php

declare(strict_types=1);

namespace AdyenPayment\Doctrine\Writer;

use AdyenPayment\Models\Payment\PaymentMethod;
use AdyenPayment\Models\PaymentMethod\ImportResult;
use Shopware\Models\Shop\Shop;

interface PaymentMethodWriterInterface
{
    public function __invoke(PaymentMethod $adyenPaymentMethod, Shop $shop): ImportResult;
}

//    public function __invoke(
//        PaymentMethod $adyenPaymentMethod,
//        Shop $shop
//    ): ImportResult {
//        $shops = new ArrayCollection([$shop]);
//        $countries = $this->fetchCountryList();
//
//        $existingPaymentMeanId = $this->paymentAttributes->fetchPaymentMeanIdByAdyenType($adyenPaymentMethod->getType());
//        $existingPaymentMethod = null;
//        if (null !== $existingPaymentMeanId) {
//            $existingPaymentMethod = $this->paymentRepository->findOneBy([
//                'id' => $existingPaymentMeanId
//            ]);
//        }
//
//        if ($existingPaymentMethod) {
//            $existingPaymentMethod = $existingPaymentMethod->updateFromAdyenPaymentMethod(
//                $adyenPaymentMethod,
//                $shops,
//                $countries
//            );
//            $this->storeAdyenPaymentMethodType(
//                $existingPaymentMethod->getId(),
//                $adyenPaymentMethod->getType()
//            );
//
//            return ImportResult::success($shop, $adyenPaymentMethod);
//        }
//
//        $shopwarePaymentModel = Payment::createFromAdyenPaymentMethod($adyenPaymentMethod, $shops, $countries);
//
//        $this->entityManager->persist($shopwarePaymentModel);
//        $this->entityManager->flush();
//
//        $this->storeAdyenPaymentMethodType(
//            $shopwarePaymentModel->getId(),
//            $adyenPaymentMethod->getType()
//        );
//
//        return ImportResult::success($shop, $adyenPaymentMethod);
//    }
