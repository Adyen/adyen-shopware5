<?php

namespace AdyenPayment\Components\Integration\PaymentProcessors;

use Adyen\Core\BusinessLogic\Domain\Checkout\PaymentRequest\Factory\PaymentRequestBuilder;
use Adyen\Core\BusinessLogic\Domain\Checkout\PaymentRequest\Models\LineItem;
use Adyen\Core\BusinessLogic\Domain\Checkout\PaymentRequest\Models\StartTransactionRequestContext;
use Adyen\Core\BusinessLogic\Domain\Integration\Processors\LineItemsProcessor as BaseLineItemsProcessor;
use Shopware\Models\Article\Article;
use Shopware\Models\Article\Repository as ArticleRepository;

/**
 * Class LineItemsProcessor
 *
 * @package AdyenPayment\Components\Integration\PaymentProcessors
 */
class LineItemsProcessor implements BaseLineItemsProcessor
{
    /**
     * @var ArticleRepository
     */
    private $articleRepository;

    /**
     * @param ArticleRepository $articleRepository
     */
    public function __construct(ArticleRepository $articleRepository)
    {
        $this->articleRepository = $articleRepository;
    }

    public function process(PaymentRequestBuilder $builder, StartTransactionRequestContext $context): void
    {
        $basket = $context->getCheckoutSession()->get('basket');
        $basketContent = $basket['content'];
        $lineItems = [];

        foreach ($basketContent as $item) {
            $amountExcludingTax = $item['amountnetNumeric'] ? round($item['amountnetNumeric'], 2) : 0;
            $taxPercentage = $item['tax_rate'] ?? 0;
            $amountIncludingTax = $item['amountNumeric'] ?? 0;
            $taxAmount = $amountIncludingTax - $amountExcludingTax;
            /** @var Article[] $articles */
            $articles = $this->articleRepository->getArticleQuery($item['additional_details']['articleID'])->getResult();
            $article = $articles[0] ?? null;

            $lineItems[] = new LineItem(
                $item['articleID'] ?? '',
                $amountExcludingTax * 100,
                $amountIncludingTax * 100,
                $taxAmount * 100,
                $taxPercentage * 100,
                substr($item['additional_details']['description'] !== '' ? $item['additional_details']['description']
                    : $item['additional_details']['description_long'], 0, 124),
                $item['additional_details']['image']['source'] ?? '',
                $article ? $article->getCategories()->first()->getName() : '',
                $item['quantity'] ?? 0
            );
        }

        $builder->setLineItems($lineItems);
    }
}
