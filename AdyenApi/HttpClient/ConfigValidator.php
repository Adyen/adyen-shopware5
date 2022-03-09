<?php

declare(strict_types=1);

namespace AdyenPayment\AdyenApi\HttpClient;

use Adyen\AdyenException;
use Adyen\Service\Checkout;
use AdyenPayment\Components\Configuration;
use AdyenPayment\Validator\ConstraintViolationFactory;
use Doctrine\Persistence\ObjectRepository;
use Shopware\Models\Shop\Shop;
use Symfony\Component\Validator\ConstraintViolationList;

class ConfigValidator
{
    private ClientFactory $adyenApiFactory;
    private Configuration $configuration;
    private ObjectRepository $shopRepository;

    public function __construct(
        ClientFactory $adyenApiFactory,
        Configuration $configuration,
        ObjectRepository $shopRepository
    ) {
        $this->adyenApiFactory = $adyenApiFactory;
        $this->configuration = $configuration;
        $this->shopRepository = $shopRepository;
    }

    public function validate(int $shopId): ConstraintViolationList
    {
        $shop = $this->shopRepository->find($shopId);
        if (!$shop) {
            return new ConstraintViolationList([
                ConstraintViolationFactory::create('Shop not found for ID "'.$shopId.'".'),
            ]);
        }

        $violations = $this->validateConfig($shop);
        if ($violations->count()) {
            return $violations;
        }

        return $this->validateConnection($shop);
    }

    private function validateConfig(Shop $shop): ConstraintViolationList
    {
        $violations = new ConstraintViolationList();
        $shopApiKey = $this->configuration->getApiKey($shop);
        $shopMerchantAccount = $this->configuration->getMerchantAccount($shop);
        if ('' === $shopApiKey) {
            $violations->add(ConstraintViolationFactory::create('Missing configuration: API key.'));
        }

        if ('' === $shopMerchantAccount) {
            $violations->add(ConstraintViolationFactory::create('Missing configuration: merchant account.'));
        }

        return $violations;
    }

    private function validateConnection(Shop $shop): ConstraintViolationList
    {
        try {
            $adyenClient = $this->adyenApiFactory->provide($shop);
            $checkout = new Checkout($adyenClient);
            $checkout->paymentMethods([
                'merchantAccount' => $this->configuration->getMerchantAccount($shop),
            ]);
        } catch (AdyenException $exception) {
            return new ConstraintViolationList([
                ConstraintViolationFactory::create('Adyen API failed, check error logs'),
            ]);
        }

        return new ConstraintViolationList();
    }
}
