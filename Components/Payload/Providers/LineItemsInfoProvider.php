<?php

namespace MeteorAdyen\Components\Payload\Providers;

use Adyen\Util\Currency;
use Enlight_Event_Exception;
use Enlight_Exception;
use MeteorAdyen\Components\Calculator\PriceCalculationService;
use MeteorAdyen\Components\Payload\PaymentContext;
use MeteorAdyen\Components\Payload\PaymentPayloadProvider;
use Shopware\Models\Order\Detail;
use Zend_Db_Adapter_Exception;

/**
 * Class LineItemsInfoProvider
 * @package MeteorAdyen\Components\Payload\Providers
 */
class LineItemsInfoProvider implements PaymentPayloadProvider
{
    /**
     * @var PriceCalculationService
     */
    private $priceCalculationService;

    /**
     * @var Currency
     */
    private $adyenCurrency;

    public function __construct(PriceCalculationService $priceCalculationService)
    {
        $this->priceCalculationService = $priceCalculationService;
        $this->adyenCurrency = new Currency();
    }

    /**
     * @param PaymentContext $context
     * @return array
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

    /**
     * @param PaymentContext $context
     * @return array
     */
    private function buildOrderLines(PaymentContext $context)
    {
        $orderLines = [];
        $currencyCode = $context->getOrder()->getCurrency();

        /** @var Detail $detail */
        foreach ($context->getOrder()->getDetails() as $detail) {
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

    /**
     * @param PaymentContext $context
     * @return array
     */
    private function buildShippingLines(PaymentContext $context)
    {
        $currencyCode = $context->getOrder()->getCurrency();
        $amountExcludingTax = $this->adyenCurrency->sanitize(
            $context->getOrder()->getInvoiceShippingNet(), $currencyCode
        );
        $amountIncludingTax = $this->adyenCurrency->sanitize(
            $context->getOrder()->getInvoiceShipping(), $currencyCode
        );

        $shippingLines[] = [
            'quantity' => 1,
            'amountExcludingTax' => $amountExcludingTax,
            'taxPercentage' => $this->adyenCurrency->sanitize(
                $context->getOrder()->getInvoiceShippingTaxRate(), $currencyCode
            ),
            'description' => $context->getOrder()->getDispatch()->getName(),
            'id' => $context->getOrder()->getDispatch()->getId(),
            'taxAmount' => $amountIncludingTax - $amountExcludingTax,
            'amountIncludingTax' => $amountIncludingTax
        ];

        return $shippingLines;
    }
}
