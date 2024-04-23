<?php

namespace AdyenPayment\Components;

use Adyen\Core\BusinessLogic\AdminAPI\AdminAPI;
use Adyen\Core\BusinessLogic\AdminAPI\Integration\Response\StateResponse;
use Adyen\Core\BusinessLogic\Domain\Checkout\PaymentRequest\Exceptions\InvalidCurrencyCode;
use Adyen\Core\BusinessLogic\Domain\Checkout\PaymentRequest\Models\PaymentMethodResponse;
use Adyen\Core\BusinessLogic\Domain\Payment\Models\PaymentMethod;
use AdyenPayment\AdyenPayment;
use AdyenPayment\Utilities\Plugin;
use AdyenPayment\Utilities\Shop;
use DateTime;
use Shopware_Components_Snippet_Manager;

/**
 * Class PaymentMeansEnricher
 *
 * @package AdyenPayment\Components
 */
class PaymentMeansEnricher
{
    /**
     * @var Shopware_Components_Snippet_Manager
     */
    private $snippets;
    /**
     * @var CheckoutConfigProvider
     */
    private $checkoutConfigProvider;

    /**
     * @param Shopware_Components_Snippet_Manager $snippets
     * @param CheckoutConfigProvider $checkoutConfigProvider
     */
    public function __construct(
        Shopware_Components_Snippet_Manager $snippets,
        CheckoutConfigProvider $checkoutConfigProvider
    ) {
        $this->snippets = $snippets;
        $this->checkoutConfigProvider = $checkoutConfigProvider;
    }

    /**
     * @param array $paymentMeans
     *
     * @return array
     *
     * @throws InvalidCurrencyCode
     */
    public function enrich(array $paymentMeans): array
    {
        if (AdminAPI::get()->integration(Shop::getShopId())->getState()->toArray() !== StateResponse::dashboard()->toArray()) {
            $this->removeAdyenPaymentMeans($paymentMeans);

            return $paymentMeans;
        }

        return array_merge(
            $this->enrichPaymentMeans($paymentMeans),
            $this->enrichStoredCreditCardPaymentMeans($paymentMeans),
            $this->enrichRecurringPaymentMeans($paymentMeans)
        );
    }

    /**
     * @param array $paymentMean
     * @param string $selectedStoredPaymentMethodId
     *
     * @return array
     * @throws InvalidCurrencyCode
     */
    public function enrichPaymentMean(array $paymentMean, string $selectedStoredPaymentMethodId = ''): array
    {
        $umbrellaPaymentMean = $this->findUmbrellaPaymentMean([$paymentMean]);
        if (!empty($umbrellaPaymentMean) && $paymentMean['name'] === AdyenPayment::STORED_PAYMENT_UMBRELLA_NAME) {
            $enriched = $this->enrichStoredCreditCardPaymentMeans([$paymentMean], $selectedStoredPaymentMethodId);

            return !empty($enriched) ? current($enriched) : $paymentMean;
        }

        if (!empty($selectedStoredPaymentMethodId) && $paymentMean['name'] !== AdyenPayment::STORED_PAYMENT_UMBRELLA_NAME) {
            $enriched = $this->enrichRecurringPaymentMeans([$paymentMean]);

            return !empty($enriched) ? current($enriched) : $paymentMean;
        }

        $enrichedPaymentMeans = $this->enrichPaymentMeans([$paymentMean]);

        return !empty($enrichedPaymentMeans) ? current($enrichedPaymentMeans) : [];
    }

    /**
     * @param array $paymentMeans
     *
     * @return array
     *
     * @throws InvalidCurrencyCode
     */
    private function enrichPaymentMeans(array $paymentMeans): array
    {
        $paymentMethodConfigsMap = $this->getPaymentMethodConfigurationMap();
        $totalProductsAmount = Shopware()->Modules()->Basket()->sGetAmountArticles();
        $currencyFactor = Shopware()->Shop()->getCurrency()->getFactor();

        return array_map(
            static function (array $paymentMean) use (
                $totalProductsAmount,
                $currencyFactor,
                $paymentMethodConfigsMap
            ) {
                $adyenPaymentType = Plugin::getAdyenPaymentType($paymentMean['name']);
                $paymentMean['isAdyenPaymentMethod'] = Plugin::isAdyenPaymentMean($paymentMean['name']);
                $paymentMean['isStoredPaymentMethod'] = false;
                $paymentMean['adyenPaymentType'] = $adyenPaymentType;
                if (
                    $paymentMean['isAdyenPaymentMethod'] &&
                    array_key_exists($paymentMean['adyenPaymentType'], $paymentMethodConfigsMap)
                ) {
                    $paymentMethod = $paymentMethodConfigsMap[$paymentMean['adyenPaymentType']];
                    $paymentMean['image'] = $paymentMethod->getLogo();
                    $paymentMean['description'] = $paymentMethod->getName();
                    $paymentMean['additionaldescription'] = $paymentMethod->getDescription();
                    $paymentMean['surchargeAmount'] = self::calculateSurchargeAmount(
                        $paymentMethod,
                        $currencyFactor,
                        (float)$totalProductsAmount['totalAmount']
                    );
                    $paymentMean['surchargeLimit'] = self::calculateSurchargeLimit($paymentMethod);
                }

                return $paymentMean;
            },
            $this->getOnlyAvailablePaymentMeans($paymentMeans)
        );
    }

    /**
     * @param PaymentMethod $paymentMethod
     * @param float $currencyFactor
     * @param float $productAmount
     *
     * @return float
     */
    private static function calculateSurchargeAmount(
        PaymentMethod $paymentMethod,
        float $currencyFactor,
        float $productAmount
    ): float {
        $surchargeType = $paymentMethod->getSurchargeType();
        $fixedAmount = (float)$paymentMethod->getFixedSurcharge() * $currencyFactor;
        $limit = (float)$paymentMethod->getSurchargeLimit() * $currencyFactor;
        $percent = $paymentMethod->getPercentSurcharge();

        if ($surchargeType === 'fixed') {
            return $fixedAmount;
        }

        if ($surchargeType === 'percent') {
            $amount = ($productAmount) / 100 * $percent;

            return $limit && $amount > $limit ? $limit : $amount;
        }

        if ($surchargeType === 'combined') {
            $amount = ($productAmount + $fixedAmount) / 100 * $percent;

            return $limit ? (min($amount + $fixedAmount, $limit)) : $amount + $fixedAmount;
        }

        return 0;
    }

    /**
     * @param PaymentMethod $paymentMethod
     *
     * @return float
     */
    private static function calculateSurchargeLimit(PaymentMethod $paymentMethod): float
    {
        $surchargeType = $paymentMethod->getSurchargeType();

        if ($surchargeType === 'fixed') {
            return 0;
        }

        if ($surchargeType === 'percent') {
            return $paymentMethod->getSurchargeLimit() ?? 0;
        }

        if ($surchargeType === 'combined') {
            return $paymentMethod->getFixedSurcharge() && $paymentMethod->getSurchargeLimit(
            ) ? $paymentMethod->getSurchargeLimit() - $paymentMethod->getFixedSurcharge(
                ) : $paymentMethod->getSurchargeLimit() ?? 0;
        }

        return 0;
    }

    /**
     * @param array $paymentMeans
     * @param string $selectedStoredPaymentMethodId
     *
     * @return array
     *
     * @throws InvalidCurrencyCode
     */
    private function enrichStoredCreditCardPaymentMeans(
        array $paymentMeans,
        string $selectedStoredPaymentMethodId = ''
    ): array {
        $umbrellaPaymentMean = $this->findUmbrellaPaymentMean($paymentMeans);
        if (empty($umbrellaPaymentMean)) {
            return [];
        }

        $paymentMethodConfigsMap = $this->getPaymentMethodConfigurationMap();
        $checkoutConfig = $this->checkoutConfigProvider->getCheckoutConfig();

        if (!$checkoutConfig->isSuccessful()) {
            return [];
        }

        $storedPaymentMethodsResponse = $checkoutConfig->getStoredPaymentMethodResponse();
        if (!empty($selectedStoredPaymentMethodId)) {
            $storedPaymentMethodsResponse = $this->filterSelectedStoredPaymentMethod(
                $storedPaymentMethodsResponse,
                $selectedStoredPaymentMethodId
            );
        }

        return array_map(
            function (
                PaymentMethodResponse $paymentMethodResponse
            ) use (
                $umbrellaPaymentMean,
                $paymentMethodConfigsMap
            ) {
                $paymentMean = [
                    'isAdyenPaymentMethod' => true,
                    'isStoredPaymentMethod' => true,
                    'storedPaymentMethodId' => $paymentMethodResponse->getMetaData()['id'],
                    'adyenPaymentType' => $paymentMethodResponse->getType(),
                    'description' => $paymentMethodResponse->getName(),
                    'additionaldescription' => sprintf(
                        $this->snippets
                            ->getNamespace('frontend/adyen/checkout')
                            ->get(
                                'payment/adyen/card_number_ending_on',
                                'Card number ending on: %s',
                                true
                            ),
                        $paymentMethodResponse->getMetaData()['lastFour']
                    ),
                ];

                if (array_key_exists($paymentMean['adyenPaymentType'], $paymentMethodConfigsMap)) {
                    $paymentMethod = $paymentMethodConfigsMap[$paymentMean['adyenPaymentType']];
                    $paymentMean['image'] = $paymentMethod->getLogo();
                    $paymentMean['additionaldescription'] = implode(
                        '. ', [$paymentMethod->getDescription(), $paymentMean['additionaldescription']]
                    );
                }

                return array_merge($umbrellaPaymentMean, $paymentMean);
            },
            $storedPaymentMethodsResponse
        );
    }

    /**
     * @param array $paymentMeans
     *
     * @return array
     *
     * @throws InvalidCurrencyCode
     */
    private function enrichRecurringPaymentMeans(array $paymentMeans): array
    {
        $paymentMethodConfigsMap = $this->getPaymentMethodConfigurationMap();
        $checkoutConfig = $this->checkoutConfigProvider->getCheckoutConfig();

        if (!$checkoutConfig->isSuccessful()) {
            return [];
        }

        $storedPaymentMethodsResponse = $checkoutConfig->getRecurringPaymentMethodResponse();

        return array_map(
            function (
                PaymentMethodResponse $paymentMethodResponse
            ) use ($paymentMethodConfigsMap, $paymentMeans) {
                $shopwareMean = $this->findStoredPaymentMean($paymentMeans, $paymentMethodResponse->getType());
                $paymentMean = [
                    'isAdyenPaymentMethod' => true,
                    'isStoredPaymentMethod' => true,
                    'storedPaymentMethodId' => $paymentMethodResponse->getMetaData()['id'],
                    'adyenPaymentType' => $paymentMethodResponse->getType(),
                    'description' => $shopwareMean['description'],
                    'additionaldescription' =>
                        $this->snippets
                            ->getNamespace('frontend/adyen/checkout')
                            ->get(
                                'payment/adyen/recurring_methods_title',
                                'Recurring payment method',
                                true
                            )
                ];

                if (array_key_exists($paymentMean['adyenPaymentType'], $paymentMethodConfigsMap)) {
                    $paymentMethod = $paymentMethodConfigsMap[$paymentMean['adyenPaymentType']];
                    $paymentMean['image'] = $paymentMethod->getLogo();
                    $paymentMean['additionaldescription'] = implode(
                        '. ', [$paymentMethod->getDescription(), $paymentMean['additionaldescription']]
                    );
                }

                return array_merge($shopwareMean, $paymentMean);
            },
            $storedPaymentMethodsResponse
        );
    }

    /**
     * @param array $paymentMeans
     *
     * @return array
     *
     * @throws InvalidCurrencyCode
     */
    private function getOnlyAvailablePaymentMeans(array $paymentMeans): array
    {
        $checkoutConfig = $this->checkoutConfigProvider->getCheckoutConfig();

        $availablePaymentMethodTypes = [];
        if ($checkoutConfig->isSuccessful()) {
            $availablePaymentMethodTypes = array_map(static function (PaymentMethodResponse $paymentMethodResponse) {
                return $paymentMethodResponse->getType();
            }, $checkoutConfig->getPaymentMethodResponse());
        }

        return array_filter(
            array_map(static function (array $paymentMean) use ($availablePaymentMethodTypes) {
                if (!Plugin::isAdyenPaymentMean($paymentMean['name'])) {
                    return $paymentMean;
                }

                $paymentMeanType = Plugin::getAdyenPaymentType($paymentMean['name']);

                return in_array($paymentMeanType, $availablePaymentMethodTypes, true) ? $paymentMean : null;
            }, $paymentMeans)
        );
    }

    /**
     * @return array<string, PaymentMethod>
     * @throws InvalidCurrencyCode
     */
    private function getPaymentMethodConfigurationMap(): array
    {
        $checkoutConfig = $this->checkoutConfigProvider->getCheckoutConfig();
        if (!$checkoutConfig->isSuccessful()) {
            return [];
        }

        $paymentMethodConfigsMap = [];
        foreach ($checkoutConfig->getPaymentMethodsConfiguration() as $paymentMethodConfig) {
            $paymentMethodConfigsMap[$paymentMethodConfig->getCode()] = $paymentMethodConfig;
        }

        return $paymentMethodConfigsMap;
    }

    /**
     * @param array $paymentMeans
     *
     * @return array
     */
    private function findUmbrellaPaymentMean(array $paymentMeans): array
    {
        foreach ($paymentMeans as $paymentMean) {
            if ($paymentMean['name'] === AdyenPayment::STORED_PAYMENT_UMBRELLA_NAME) {
                return $paymentMean;
            }
        }

        return [];
    }

    /**
     * @param array $paymentMeans
     * @param string $methodType
     *
     * @return array
     */
    private function findStoredPaymentMean(array $paymentMeans, string $methodType): array
    {
        foreach ($paymentMeans as $paymentMean) {
            if (str_replace('adyen_', '', $paymentMean['name']) === $methodType) {
                return $paymentMean;
            }
        }

        return [];
    }

    /**
     * @param PaymentMethodResponse[] $storedPaymentMethodsResponse
     * @param string $selectedStoredPaymentMethodId
     *
     * @return PaymentMethodResponse[]
     */
    private function filterSelectedStoredPaymentMethod(
        array $storedPaymentMethodsResponse,
        string $selectedStoredPaymentMethodId
    ): array {
        foreach ($storedPaymentMethodsResponse as $paymentMethodResponse) {
            if ($paymentMethodResponse->getMetaData()['id'] === $selectedStoredPaymentMethodId) {
                return [$paymentMethodResponse];
            }
        }

        return [];
    }

    /**
     * @param array $paymentMeans
     *
     * @return void
     */
    private function removeAdyenPaymentMeans(array &$paymentMeans)
    {
        foreach ($paymentMeans as $key => $paymentMean) {
            if (strpos($paymentMean['name'], 'adyen') !== false) {
                unset($paymentMeans[$key]);
            }
        }
    }
}
