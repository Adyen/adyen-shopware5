<?php

declare(strict_types=1);

namespace AdyenPayment\Components\Payload\Providers;

use Adyen\Util\Currency;
use AdyenPayment\Components\Calculator\PriceCalculationService;
use AdyenPayment\Components\Payload\PaymentContext;
use AdyenPayment\Components\Payload\PaymentPayloadProvider;
use Enlight_Event_Exception;
use Enlight_Exception;
use Psr\Log\LoggerInterface;
use Shopware\Models\Order\Detail;
use Zend_Db_Adapter_Exception;

class LineItemsInfoProvider implements PaymentPayloadProvider
{
    /** @var PriceCalculationService */
    private $priceCalculationService;

    /** @var LoggerInterface */
    private $logger;

    /** @var Currency */
    private $adyenCurrency;

    public function __construct(
        PriceCalculationService $priceCalculationService,
        LoggerInterface $logger
    ) {
        $this->priceCalculationService = $priceCalculationService;
        $this->logger = $logger;
        $this->adyenCurrency = new Currency();
    }

    /**
     * @throws Enlight_Event_Exception
     * @throws Enlight_Exception
     * @throws Zend_Db_Adapter_Exception
     */
    public function provide(PaymentContext $context): array
    {
        return [
            'lineItems' => array_merge(
                $this->buildOrderLines($context),
                $this->buildShippingLines($context)
            ),
        ];
    }

    private function buildOrderLines(PaymentContext $context): array
    {
        $orderLines = [];
        $currencyCode = $context->getOrder()->getCurrency();

        /** @var Detail $detail */
        foreach ($context->getOrder()->getDetails() as $detail) {
            if (empty($detail->getArticleName())) {
                $this->logger->warning(
                    sprintf('Skipped order detail of order #%s - empty article name.', $context->getOrder()->getId())
                );

                continue;
            }

            $orderLines[] = [
                'quantity' => $detail->getQuantity(),
                'amountExcludingTax' => $this->adyenCurrency->sanitize(
                    $this->priceCalculationService->getAmountExcludingTax($detail->getPrice(), $detail->getTaxRate()),
                    $currencyCode
                ),
                'taxPercentage' => $this->adyenCurrency->sanitize($detail->getTaxRate(), $currencyCode),
                'description' => $detail->getArticleName(),
                'id' => $detail->getId(),
                'taxAmount' => $this->adyenCurrency->sanitize(
                    $this->priceCalculationService->getTaxAmount($detail->getPrice(), $detail->getTaxRate()),
                    $currencyCode
                ),
                'amountIncludingTax' => $this->adyenCurrency->sanitize($detail->getPrice(), $currencyCode),
            ];
        }

        return $orderLines;
    }

    private function buildShippingLines(PaymentContext $context): array
    {
        $currencyCode = $context->getOrder()->getCurrency();
        $amountExcludingTax = $this->adyenCurrency->sanitize(
            $context->getOrder()->getInvoiceShippingNet(),
            $currencyCode
        );
        $amountIncludingTax = $this->adyenCurrency->sanitize(
            $context->getOrder()->getInvoiceShipping(),
            $currencyCode
        );
        $dispatch = $context->getOrder()->getDispatch();

        if (!$dispatch || !$dispatch->getId()) {
            return [];
        }

        return [
            [
                'quantity' => 1,
                'amountExcludingTax' => $amountExcludingTax,
                'taxPercentage' => $this->adyenCurrency->sanitize(
                    $context->getOrder()->getInvoiceShippingTaxRate(),
                    $currencyCode
                ),
                'description' => $dispatch->getName(),
                'id' => $dispatch->getId(),
                'taxAmount' => $amountIncludingTax - $amountExcludingTax,
                'amountIncludingTax' => $amountIncludingTax,
            ],
        ];
    }
}
