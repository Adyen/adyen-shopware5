<?php

declare(strict_types=1);

namespace AdyenPayment\Import;

use AdyenPayment\Components\Adyen\Mapper\PaymentMethodMapperInterface;
use AdyenPayment\Components\Adyen\PaymentMethod\PaymentMethodsProviderInterface;
use AdyenPayment\Doctrine\Writer\PaymentMethodWriter;
use AdyenPayment\Models\PaymentMethod\ImportResult;
use AdyenPayment\Rule\AdyenApi\UsedFallbackConfigRuleInterface;
use Doctrine\Common\Persistence\ObjectRepository;
use Shopware\Components\Model\ModelManager;
use Shopware\Models\Shop\Shop;

class PaymentMethodImporter implements PaymentMethodImporterInterface
{
    /**
     * @var PaymentMethodsProviderInterface
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
     * @var PaymentMethodMapperInterface
     */
    private $paymentMethodMapper;
    /**
     * @var PaymentMethodWriter
     */
    private $paymentMethodWriter;
    /** @var ModelManager */
    private $entityManager;

    public function __construct(
        PaymentMethodsProviderInterface $paymentMethodsProvider,
        ObjectRepository $shopRepository,
        UsedFallbackConfigRuleInterface $usedFallbackConfigRule,
        PaymentMethodMapperInterface $paymentMethodMapper,
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
