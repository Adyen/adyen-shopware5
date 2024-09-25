<?php

namespace AdyenPayment\Components;

use Adyen\Core\BusinessLogic\AdminAPI\Response\ErrorResponse;
use Adyen\Core\BusinessLogic\AdminAPI\Response\Response;
use Adyen\Core\BusinessLogic\CheckoutAPI\CheckoutAPI;
use Adyen\Core\BusinessLogic\CheckoutAPI\CheckoutConfig\Request\PaymentCheckoutConfigRequest;
use Adyen\Core\BusinessLogic\CheckoutAPI\CheckoutConfig\Response\PaymentCheckoutConfigResponse;
use Adyen\Core\BusinessLogic\Domain\Checkout\PaymentRequest\Exceptions\InvalidCurrencyCode;
use Adyen\Core\BusinessLogic\Domain\Checkout\PaymentRequest\Models\Amount\Amount;
use Adyen\Core\BusinessLogic\Domain\Checkout\PaymentRequest\Models\Amount\Currency;
use Adyen\Core\BusinessLogic\Domain\Checkout\PaymentRequest\Models\Country;
use Adyen\Core\BusinessLogic\Domain\Checkout\PaymentRequest\Models\PaymentMethodCode;
use Adyen\Core\BusinessLogic\Domain\Payment\Models\MethodAdditionalData\CardConfig;
use AdyenPayment\Utilities\Shop;
use Enlight_Components_Session_Namespace;
use Shopware\Models\Customer\Customer;

/**
 * Class CheckoutConfigProvider
 *
 * @package AdyenPayment\Components
 */
class CheckoutConfigProvider
{
    /**
     * In-memory cache of checkout config responses for a specific request parameters
     *
     * @var array<string, PaymentCheckoutConfigResponse>
     */
    private $checkoutConfigCache = [];

    /**
     * @var Enlight_Components_Session_Namespace
     */
    private $session;

    public function __construct(Enlight_Components_Session_Namespace $session)
    {
        $this->session = $session;
    }

    /**
     * @param Amount|null $forceAmount
     *
     * @return Response
     *
     * @throws InvalidCurrencyCode
     */
    public function getCheckoutConfig(?Amount $forceAmount = null): Response
    {
        $request = $this->buildConfigRequest($forceAmount);

        $response = $this->getCheckoutConfigResponse($request, false, static function (PaymentCheckoutConfigRequest $request) {
            return CheckoutAPI::get()
                ->checkoutConfig(Shop::getShopId())
                ->getPaymentCheckoutConfig($request);
        });

        if (!$response->isSuccessful()) {
            return $response;
        }

        $userData = Shopware()->Modules()->Admin()->sGetUserData();
        if (
            !empty($userData['additional']['user']['accountmode']) &&
            (int)$userData['additional']['user']['accountmode'] === Customer::ACCOUNT_MODE_FAST_LOGIN
        ) {
            $this->disableCardsSingleClickPayment($response);
        }

        return $response;
    }

    /**
     * @param Amount|null $forceAmount
     *
     * @return Response
     *
     * @throws InvalidCurrencyCode
     */
    public function getExpressCheckoutConfig(Amount $forceAmount): Response
    {
        $request = $this->buildConfigRequest($forceAmount);

        return $this->getCheckoutConfigResponse($request, true, static function (PaymentCheckoutConfigRequest $request) {
            return CheckoutAPI::get()
                ->checkoutConfig(Shop::getShopId())
                ->getExpressPaymentCheckoutConfig($request);
        });
    }

    /**
     * @param Amount|null $forceAmount
     * @return PaymentCheckoutConfigRequest
     * @throws InvalidCurrencyCode
     */
    private function buildConfigRequest(?Amount $forceAmount = null): PaymentCheckoutConfigRequest
    {
        $country = null;
        $isGuest = false;

        if ($this->getUser() && isset($this->getUser()['additional']['country']['countryiso'])) {
            $country = Country::fromIsoCode($this->getUser()['additional']['country']['countryiso']);
        }

        if (
            !$country &&
            Shopware()->Modules() &&
            ($sAdmin = Shopware()->Modules()->Admin()) &&
            ($userData = $sAdmin->sGetUserData()) &&
            isset($userData['additional']['country']['countryiso'])
        ) {
            $country = Country::fromIsoCode($userData['additional']['country']['countryiso']);
        }

        $shop = Shopware()->Shop();
        $userId = (int)$this->session->offsetGet('sUserId');
        $shopperReference = ($userId !== 0) ? $shop->getHost() . '_' . Shop::getShopId() . '_' . $userId : null;
        $shopperEmail = null;

        if (!$userId) {
            $isGuest = true;
        }

        if (
            ($sAdmin = Shopware()->Modules()->Admin()) &&
            ($userData = $sAdmin->sGetUserData()) &&
            isset($userData['additional']['user']['email'])
        ) {
            $shopperEmail = $userData['additional']['user']['email'];
        }

        return new PaymentCheckoutConfigRequest(
            $this->getAmount($forceAmount),
            $country,
            Shopware()->Shop()->getLocale()->getLocale(),
            $shopperReference,
            $shopperEmail,
            $shop->getName(),
            $isGuest
        );
    }

    /**
     * Gets the response from cache or makes the response and cache the result by calling $responseCallback
     *
     * @param PaymentCheckoutConfigRequest $request
     * @param bool $isExpressCheckout
     * @param callable $responseCallback
     *
     * @return Response|PaymentCheckoutConfigResponse
     */
    private function getCheckoutConfigResponse(
        PaymentCheckoutConfigRequest $request,
        bool $isExpressCheckout,
        callable $responseCallback
    ): Response {
        $cacheKey = implode('-', [
            $request->getShopperLocale(),
            $request->getAmount()->getValue(),
            (string)$request->getAmount()->getCurrency(),
            (string)$request->getCountry(),
            $request->getShopperReference(),
            $isExpressCheckout ? 'express' : 'standard'
        ]);

        if (array_key_exists($cacheKey, $this->checkoutConfigCache)) {
            return $this->checkoutConfigCache[$cacheKey];
        }

        $configResponse = $responseCallback($request);

        if (!$configResponse->isSuccessful()) {
            return $configResponse;
        }

        $this->checkoutConfigCache[$cacheKey] = $configResponse;

        return $this->checkoutConfigCache[$cacheKey];
    }

    /**
     * @param Amount|null $forceAmount
     * @return Amount
     * @throws InvalidCurrencyCode
     */
    private function getAmount(?Amount $forceAmount = null): Amount
    {
        if ($forceAmount) {
            return $forceAmount;
        }

        $currencyName = $this->getBasket()['sCurrencyName'] ?? null;
        if (!$currencyName && Shopware()->Shop() && Shopware()->Shop()->getCurrency()) {
            $currencyName = Shopware()->Shop()->getCurrency()->getCurrency();
        }

        $cartAmount = $this->getBasketAmount();
        if (!$cartAmount && Shopware()->Modules()->Basket()) {
            $cartAmount = (float)Shopware()->Modules()->Basket()->sGetAmount()['totalAmount'];
        }

        return Amount::fromFloat(
            $cartAmount,
            Currency::fromIsoCode($currencyName ?? 'EUR')
        );
    }

    /**
     * Returns the full user data as array.
     *
     * @return array|null
     */
    private function getUser(): ?array
    {
        if (!empty($this->session->sOrderVariables['sUserData'])) {
            return $this->session->sOrderVariables['sUserData'];
        }

        return null;
    }

    /**
     * Returns the full basket data as array.
     *
     * @return array|null
     */
    private function getBasket(): ?array
    {
        if (!empty($this->session->sOrderVariables['sBasket'])) {
            return $this->session->sOrderVariables['sBasket'];
        }

        return null;
    }

    /**
     * Return the full amount to pay.
     *
     * @return float|null
     */
    private function getBasketAmount(): ?float
    {
        $user = $this->getUser();
        $basket = $this->getBasket();
        if (!empty($user['additional']['charge_vat'])) {
            return empty($basket['AmountWithTaxNumeric']) ? $basket['AmountNumeric'] : $basket['AmountWithTaxNumeric'];
        }

        return $basket['AmountNetNumeric'];
    }

    private function disableCardsSingleClickPayment(PaymentCheckoutConfigResponse $response): void
    {
        foreach ($response->getPaymentMethodsConfiguration() as $method) {
            if (PaymentMethodCode::scheme()->equals($method->getCode())) {
                /** @var CardConfig $additionalData */
                $additionalData = $method->getAdditionalData();
                $method->setAdditionalData(new CardConfig(
                    $additionalData->isShowLogos(),
                    false,
                    $additionalData->isClickToPay(),
                    $additionalData->isInstallments(),
                    $additionalData->isInstallmentAmounts(),
                    $additionalData->isSendBasket(),
                    $additionalData->getInstallmentCountries(),
                    $additionalData->getMinimumAmount(),
                    $additionalData->getNumberOfInstallments()
                ));

                return;
            }
        }
    }
}
