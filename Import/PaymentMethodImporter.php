<?php

declare(strict_types=1);

namespace AdyenPayment\Import;

use AdyenPayment\Provider\PaymentMethodsProvider;
use Doctrine\Common\Persistence\ObjectRepository;

class PaymentMethodImporter implements PaymentMethodImporterInterface
{
    /**
     * @var PaymentMethodsProvider
     */
    private $paymentMethodsProvider;
    /**
     * @var ObjectRepository
     */
    private $shopRepository;

    public function __construct(
        PaymentMethodsProvider $paymentMethodsProvider,
        ObjectRepository $shopRepository
    )
    {
        $this->paymentMethodsProvider = $paymentMethodsProvider;
        $this->shopRepository = $shopRepository;
    }

//    public function __invoke(): \Generator
//    {
//        // TODO actual importer
//        try {
//            $models->persist();
//            yield $result;
//        } catch (\Exception $exception) {
//            $exception->getMessage();
//            yield (new Result())
//                ->setSuccess(false)
//                ->setException($exception);
//        }
//    }
    public function __invoke(): \Generator
    {
        // TODO actual importer
        $shops = $this->shopRepository->findAll();

        foreach($shops as $shop) {
            try {
                // 1. fetch
                $paymentMethods = $this->paymentMethodsProvider->getPaymentMethods($shop);

                yield $paymentMethods;
            } catch (\Exception $exception) {
//            $exception->getMessage();
//            yield (new Result())
//                ->setSuccess(false)
//                ->setException($exception);
                yield 'nok';
            }
        }
    }
}
