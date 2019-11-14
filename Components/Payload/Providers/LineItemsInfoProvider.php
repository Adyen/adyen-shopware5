<?php
declare(strict_types=1);

namespace MeteorAdyen\Components\Payload\Providers;

use Enlight_Event_Exception;
use Enlight_Exception;
use MeteorAdyen\Components\Payload\PaymentContext;
use MeteorAdyen\Components\Payload\PaymentPayloadProvider;
use Zend_Db_Adapter_Exception;

/**
 * Class LineItemsInfoProvider
 * @package MeteorAdyen\Components\Payload\Providers
 */
class LineItemsInfoProvider implements PaymentPayloadProvider
{
    /**
     * @return array
     * @throws Enlight_Event_Exception
     * @throws Enlight_Exception
     * @throws Zend_Db_Adapter_Exception
     */
    public function provide(PaymentContext $context): array
    {
        $basket = $context->getBasket()->sGetBasket();

        return [
            'lineItems' => json_encode(array_map(
                function ($item) {
                    return [
                        'quantity' => $item['quantity'],
                        'amountExcludingTax' => $item['netprice'],
                        'taxPercentage' => $item['tax_rate'],
                        'description' => $item['articlename'],
                        'id' => $item['id'],
                        'taxAmount' => $item['tax'],
                        'amountIncludingTax' => $item['amount'],
                        'taxCategory' => $item[''],
                    ];
                },
                $basket['content']
            ))
        ];
    }
}
