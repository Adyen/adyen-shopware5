<?php

namespace AdyenPayment\Components\Integration\PaymentProcessors;

use Adyen\Core\BusinessLogic\Domain\Checkout\PaymentLink\Factory\PaymentLinkRequestBuilder;
use Adyen\Core\BusinessLogic\Domain\Checkout\PaymentLink\Models\PaymentLinkRequestContext;
use Adyen\Core\BusinessLogic\Domain\Checkout\PaymentRequest\Factory\PaymentRequestBuilder;
use Adyen\Core\BusinessLogic\Domain\Checkout\PaymentRequest\Models\LineItem;
use Adyen\Core\BusinessLogic\Domain\Checkout\PaymentRequest\Models\StartTransactionRequestContext;
use Adyen\Core\BusinessLogic\Domain\Integration\Processors\PaymentRequest\LineItemsProcessor as BaseLineItemsProcessor;
use Adyen\Core\BusinessLogic\Domain\Integration\Processors\PaymentLinkRequest\LineItemsProcessor as PaymentLinkLineItemsProcessorInterface;
use AdyenPayment\Repositories\Wrapper\OrderRepository;
use Shopware\Models\Article\Article;
use Shopware\Models\Article\Repository as ArticleRepository;

/**
 * Class LineItemsProcessor
 *
 * @package AdyenPayment\Components\Integration\PaymentProcessors
 */
class LineItemsProcessor implements BaseLineItemsProcessor, PaymentLinkLineItemsProcessorInterface
{
    /**
     * @var ArticleRepository
     */
    private $articleRepository;

    /**
     * @var OrderRepository
     */
    private $orderRepository;

    /**
     * @param ArticleRepository $articleRepository
     * @param OrderRepository $orderRepository
     */
    public function __construct(ArticleRepository $articleRepository, OrderRepository $orderRepository)
    {
        $this->articleRepository = $articleRepository;
        $this->orderRepository = $orderRepository;
    }

    /**
     * @param PaymentRequestBuilder $builder
     * @param StartTransactionRequestContext $context
     *
     * @return void
     */
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
            $articles = $this->articleRepository->getArticleQuery($item['additional_details']['articleID'])->getResult(
            );
            $article = $articles[0] ?? null;

            $lineItems[] = new LineItem(
                $item['articleID'] ?? '',
                $amountExcludingTax * 100,
                $amountIncludingTax * 100,
                $taxAmount * 100,
                $taxPercentage * 100,
                substr(
                    $item['additional_details']['description'] !== '' ? $item['additional_details']['description']
                        : $item['additional_details']['description_long'],
                    0,
                    124
                ),
                $item['additional_details']['image']['source'] ?? '',
                $article ? $article->getCategories()->first()->getName() : '',
                $item['quantity'] ?? 0
            );
        }

        $builder->setLineItems($lineItems);
    }

    /**
     * @param PaymentLinkRequestBuilder $builder
     * @param PaymentLinkRequestContext $context
     *
     * @return void
     */
    public function processPaymentLink(PaymentLinkRequestBuilder $builder, PaymentLinkRequestContext $context): void
    {
        $order = $this->orderRepository->getOrderByTemporaryId($context->getReference());

        if (!$order) {
            return;
        }

        $lineItems = [];

        foreach ($order->getDetails() as $detail) {
            $priceWithTax = $detail->getPrice();
            $taxRate = $detail->getTaxRate();
            $taxAmount = ($priceWithTax * $taxRate) / 100;
            $priceWithoutTax = $priceWithTax - $taxAmount;
            $taxPercentage = ($taxAmount / $priceWithoutTax) * 100;
            /** @var Article[] $articles */
            $articles = $this->articleRepository->getArticleQuery($detail->getArticleId())->getResult();
            $article = $articles[0] ?? null;

            $lineItems[] = new LineItem(
                (string)$detail->getArticleId() ?? '',
                $priceWithoutTax,
                $priceWithTax,
                ($priceWithTax * $taxRate) / 100,
                $taxPercentage,
                substr($article->getDescription() ?? '', 0, 124),
                 '',
                $article ? $article->getCategories()->first()->getName() : '',
                $detail->getQuantity() ?? ''
            );
        }

        $builder->setLineItems($lineItems);
    }
}
