<?php

namespace AdyenPayment\Components;

use Adyen\Core\BusinessLogic\Domain\Checkout\PaymentRequest\Exceptions\CurrencyMismatchException;
use Adyen\Core\BusinessLogic\Domain\Checkout\PaymentRequest\Exceptions\InvalidPaymentMethodCodeException;
use Adyen\Core\BusinessLogic\Domain\TransactionHistory\Exceptions\InvalidMerchantReferenceException;
use Adyen\Core\BusinessLogic\Domain\TransactionHistory\Services\TransactionDetailsService as BaseTransactionDetailsService;
use Symfony\Component\Intl\Intl;

/**
 * Class TransactionDetailsService
 *
 * @package AdyenPayment\Components
 */
class TransactionDetailsService extends BaseTransactionDetailsService
{
    /**
     * @param string $merchantReference
     * @param string $storeId
     *
     * @return array
     *
     * @throws CurrencyMismatchException
     * @throws InvalidMerchantReferenceException
     * @throws InvalidPaymentMethodCodeException
     */
    public function getTransactionDetails(string $merchantReference, string $storeId): array
    {
        $result = parent::getTransactionDetails($merchantReference, $storeId);

        if (class_exists('\Symfony\Component\Intl\Currencies')) {
            foreach ($result as $key => $item) {
                $result[$key]['amountCurrency'] = $item['amountCurrency'] ?
                    \Symfony\Component\Intl\Currencies::getSymbol($item['amountCurrency']) : '';
            }

            return $result;
        }

        foreach ($result as $key => $item) {
            $result[$key]['amountCurrency'] = $item['amountCurrency'] ?
                \Symfony\Component\Intl\Intl::getCurrencyBundle()->getCurrencySymbol($item['amountCurrency']) : '';
        }

        return $result;
    }
}
