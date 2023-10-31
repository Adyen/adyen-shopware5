<?php

namespace AdyenPayment\Components\Integration\PaymentProcessors;

use Adyen\Core\BusinessLogic\Domain\Checkout\PaymentRequest\Factory\PaymentRequestBuilder;
use Adyen\Core\BusinessLogic\Domain\Checkout\PaymentRequest\Models\AdditionalData\AdditionalData;
use Adyen\Core\BusinessLogic\Domain\Checkout\PaymentRequest\Models\AdditionalData\BasketItem;
use Adyen\Core\BusinessLogic\Domain\Checkout\PaymentRequest\Models\AdditionalData\RiskData;
use Adyen\Core\BusinessLogic\Domain\Checkout\PaymentRequest\Models\StartTransactionRequestContext;
use Adyen\Core\BusinessLogic\Domain\GeneralSettings\Services\GeneralSettingsService;
use Adyen\Core\BusinessLogic\Domain\Integration\Processors\PaymentRequest\BasketItemsProcessor as BaseBasketItemsProcessor;
use Shopware\Models\Article\Article;
use Shopware\Models\Article\Repository as ArticleRepository;

/**
 * Class BasketItemsProcessor
 *
 * @package AdyenPayment\Components\Integration\PaymentProcessors
 */
class BasketItemsProcessor implements BaseBasketItemsProcessor
{
    /**
     * @var GeneralSettingsService
     */
    private $generalSettingsService;
    /**
     * @var ArticleRepository
     */
    private $articleRepository;

    /**
     * @param GeneralSettingsService $generalSettingsService
     * @param ArticleRepository $articleRepository
     */
    public function __construct(GeneralSettingsService $generalSettingsService, ArticleRepository $articleRepository)
    {
        $this->generalSettingsService = $generalSettingsService;
        $this->articleRepository = $articleRepository;
    }

    /**
     * @param PaymentRequestBuilder $builder
     * @param StartTransactionRequestContext $context
     *
     * @return void
     */
    public function process(PaymentRequestBuilder $builder, StartTransactionRequestContext $context): void
    {
        $generalSettings = $this->generalSettingsService->getGeneralSettings();
        $basket = $context->getCheckoutSession()->get('basket');
        $user = $context->getCheckoutSession()->get('user');

        $additionalData = new AdditionalData(
            ($generalSettings && $generalSettings->isBasketItemSync())
                ? new RiskData($this->getItems($basket, $user)) : null);

        $builder->setAdditionalData($additionalData);
    }


    /**
     * @param array $basket
     * @param array $user
     *
     * @return BasketItem[]
     */
    private function getItems(array $basket, array $user): array
    {
        $items = [];
        $basketContent = $basket['content'];

        foreach ($basketContent as $item) {
            /** @var Article[] $articles */
            $articles = $this->articleRepository->getArticleQuery($item['additional_details']['articleID'])->getResult();
            $article = $articles[0] ?? null;

            $items[] = new BasketItem(
                $item['additional_details']['articleID'] ?? '',
                '',
                0,
                $article ? $article->getCategories()->first()->getName() : '',
                '',
                $basket['sCurrencyName'] ?? '',
                '',
                $item['articlename'] ?? '',
                $item['quantity'] ?? 0,
                $user['additional']['user']['email'] ?? '',
                '',
                '',
                $item['additional_details']['ean'] ?? ''
            );
        }

        return $items;
    }
}
