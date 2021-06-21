<?php

declare(strict_types=1);

namespace AdyenPayment\Import;

use AdyenPayment\Components\Adyen\Mapper\PaymentMethodMapper;
use AdyenPayment\Components\Adyen\PaymentMethod\PaymentMethodsProvider;
use AdyenPayment\Doctrine\Writer\PaymentMethodWriter;
use AdyenPayment\Models\PaymentMethod\ImportResult;
use AdyenPayment\Rule\AdyenApi\UsedFallbackConfigRuleInterface;
use Doctrine\Common\Persistence\ObjectRepository;
use Shopware\Components\Model\ModelManager;
use Shopware\Models\Shop\Shop;

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
    /**
     * @var UsedFallbackConfigRuleInterface
     */
    private $usedFallbackConfigRule;
    /**
     * @var PaymentMethodMapper
     */
    private $paymentMethodMapper;
    /**
     * @var PaymentMethodWriter
     */
    private $paymentMethodWriter;
    /** @var ModelManager */
    private $entityManager;

    public function __construct(
        PaymentMethodsProvider $paymentMethodsProvider,
        ObjectRepository $shopRepository,
        UsedFallbackConfigRuleInterface $usedFallbackConfigRule,
        PaymentMethodMapper $paymentMethodMapper,
        PaymentMethodWriter $paymentMethodWriter,
        ModelManager $entityManager
    ) {
        $this->paymentMethodsProvider = $paymentMethodsProvider;
        $this->shopRepository = $shopRepository;
        $this->usedFallbackConfigRule = $usedFallbackConfigRule;
        $this->paymentMethodMapper = $paymentMethodMapper;
        $this->paymentMethodWriter = $paymentMethodWriter;
        $this->entityManager = $entityManager;
    }

    public function __invoke(): \Generator
    {
        $shops = $this->shopRepository->findAll();

        /** @var Shop $shop */
        foreach($shops as $shop) {
            $shopId = $shop->getId();

            if (true === ($this->usedFallbackConfigRule)($shopId)) {
                continue;
            }

            try {
                $generator = $this->paymentMethodMapper->mapFromAdyen(
                    ($this->paymentMethodsProvider)($shop)
                );

                foreach ($generator as $adyenPaymentMethod) {
                    $importResult = $this->paymentMethodWriter->saveAsShopwarePaymentMethod(
                        $adyenPaymentMethod,
                        $shop
                    );
                    yield $importResult;
                }

                $this->entityManager->flush();
            } catch (\Exception $exception) {
                yield ImportResult::fromException(
                    $shop,
                    $adyenPaymentMethod,
                    $exception
                );
            }
        }
    }
}
