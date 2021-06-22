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

final class PaymentMethodImporter implements PaymentMethodImporterInterface
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
    )
    {
        $this->paymentMethodsProvider = $paymentMethodsProvider;
        $this->shopRepository = $shopRepository;
        $this->usedFallbackConfigRule = $usedFallbackConfigRule;
        $this->paymentMethodMapper = $paymentMethodMapper;
        $this->paymentMethodWriter = $paymentMethodWriter;
        $this->entityManager = $entityManager;
    }

    public function importAll(): \Generator
    {
        /** @var Shop $shop */
        foreach ($this->shopRepository->findAll() as $shop) {
            if (true === ($this->usedFallbackConfigRule)($shop->getId())) {
                continue;
            }

            yield from $this->import($shop);
        }
        $this->entityManager->flush();
    }

    public function importForShop(Shop $shop): \Generator
    {
        yield from $this->import($shop);

        $this->entityManager->flush();
    }

    private function import(Shop $shop): \Generator
    {
        try {
            $generator = $this->paymentMethodMapper->mapFromAdyen(
                ($this->paymentMethodsProvider)($shop)
            );

            foreach ($generator as $adyenPaymentMethod) {
                yield $this->paymentMethodWriter->saveAsShopwarePaymentMethod(
                    $adyenPaymentMethod,
                    $shop
                );
            }
        } catch (\Exception $exception) {
            yield ImportResult::fromException(
                $shop,
                $adyenPaymentMethod ?? null,
                $exception
            );
        }
    }
}


/***
 * ik hoor u wel
 *
 * effe kort samengevat:
 * ik zou in het cli command o
 */