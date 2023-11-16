<?php

namespace AdyenPayment\Components\Integration\PaymentProcessors;

use Adyen\Core\BusinessLogic\Domain\Checkout\PaymentLink\Factory\PaymentLinkRequestBuilder;
use Adyen\Core\BusinessLogic\Domain\Checkout\PaymentLink\Models\PaymentLinkRequestContext;
use Adyen\Core\BusinessLogic\Domain\Checkout\PaymentRequest\Factory\PaymentRequestBuilder;
use Adyen\Core\BusinessLogic\Domain\Checkout\PaymentRequest\Models\BillingAddress;
use Adyen\Core\BusinessLogic\Domain\Checkout\PaymentRequest\Models\DeliveryAddress;
use Adyen\Core\BusinessLogic\Domain\Checkout\PaymentRequest\Models\StartTransactionRequestContext;
use Adyen\Core\BusinessLogic\Domain\Integration\Processors\PaymentRequest\AddressProcessor as BaseAddressProcessor;
use Adyen\Core\BusinessLogic\Domain\Integration\Processors\PaymentLinkRequest\AddressProcessor as PaymentLinkAddressProcessorInterface;
use AdyenPayment\Repositories\Wrapper\OrderRepository;
use Shopware\Models\Country\Country;
use Shopware\Models\Country\Repository;
use Shopware\Models\Order\Billing;
use Shopware\Models\Order\Shipping;

/**
 * Class AddressProcessor
 *
 * @package AdyenPayment\Components\Integration\PaymentProcessors
 */
class AddressProcessor implements BaseAddressProcessor, PaymentLinkAddressProcessorInterface
{
    /**
     * @var Repository
     */
    private $countryRepository;

    /**
     * @var OrderRepository
     */
    private $orderRepository;

    /**
     * @param Repository $countryRepository
     * @param OrderRepository $orderRepository
     */
    public function __construct(Repository $countryRepository, OrderRepository $orderRepository)
    {
        $this->countryRepository = $countryRepository;
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
        $billingAddressRawData = $context->getStateData()->get('billingAddress');
        $stateDataCountry = $context->getStateData()->get('countryCode');
        $deliveryAddressRawData = $context->getStateData()->get('deliveryAddress');

        $userData = $context->getCheckoutSession()->get('user');

        if (empty($userData)) {
            return;
        }

        if (!empty($userData['billingaddress'])) {
            /** @var Country[] $country */
            $country = $this->countryRepository->getCountryQuery($userData['billingaddress']['countryId'])->getResult();

            $this->setBillingAddress($billingAddressRawData, $country[0] ?: null, $userData, $builder);
            $this->setCountryCode($stateDataCountry, $country[0] ?: null, $builder);
        }

        if (!empty($userData['shippingaddress']) && empty($deliveryAddressRawData)) {
            /** @var Country[] $country */
            $country = $this->countryRepository->getCountryQuery($userData['shippingaddress']['countryId'])->getResult(
            );
            $state = null;

            if (!empty($userData['shippingaddress']['stateID'])) {
                $state = Shopware()->Models()->getRepository('Shopware\Models\Country\State')->findOneBy(
                    ['id' => $userData['shippingaddress']['stateID']]
                );
            }

            $countryIso = $country[0] ? $country[0]->getIso() : '';

            $deliveryAddress = new DeliveryAddress(
                $userData['shippingaddress']['city'] ?? '',
                $countryIso,
                '',
                $userData['shippingaddress']['zipcode'] ?? '',
                $state ? $state->getName() : $countryIso,
                $userData['shippingaddress']['street'] ?? ''
            );

            $builder->setDeliveryAddress($deliveryAddress);
        }
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

        if ($billingAddress = $order->getBilling()) {
            $country = $billingAddress->getCountry();
            $this->setBillingAddressForPaymentLink($billingAddress, $builder);
            $builder->setCountryCode($country->getIso() ?? '');
        }

        if ($shippingAddress = $order->getShipping()) {
            $this->setDeliveryAddressForPaymentLink($shippingAddress, $builder);
        }
    }

    private function setBillingAddress(
        ?array $billingAddressRawData,
        ?Country $country,
        array $userData,
        PaymentRequestBuilder $builder
    ): void {
        if (!empty($billingAddressRawData)) {
            return;
        }
        $state = Shopware()->Models()->getRepository('Shopware\Models\Country\State')->findOneBy(
            ['id' => $userData['billingaddress']['stateID']]
        );

        $billingAddress = new BillingAddress(
            $userData['billingaddress']['city'] ?? '',
            $country ? $country->getIso() : '',
            '',
            $userData['billingaddress']['zipcode'] ?? '',
            $state ? $state->getShortCode() : ($country ? $country->getIso() : ''),
            $userData['billingaddress']['street'] ?? ''
        );

        $builder->setBillingAddress($billingAddress);
    }

    /**
     * @param array|null $stateDataCountry
     * @param Country|null $country
     * @param PaymentRequestBuilder $builder
     *
     * @return void
     */
    private function setCountryCode(?array $stateDataCountry, ?Country $country, PaymentRequestBuilder $builder): void
    {
        if (!empty($stateDataCountry)) {
            return;
        }

        $builder->setCountryCode($country ? $country->getIso() : '');
    }

    /**
     * @param Billing $billingAddress
     * @param PaymentLinkRequestBuilder $builder
     *
     * @return void
     */
    private function setBillingAddressForPaymentLink(Billing $billingAddress, PaymentLinkRequestBuilder $builder): void
    {
        $billingAddress = new BillingAddress(
            $billingAddress->getCity() ?? '',
            $billingAddress->getCountry()->getIso() ?? '',
            '',
            $billingAddress->getZipCode() ?? '',
            '',
            $billingAddress->getStreet() ?? ''
        );

        $builder->setBillingAddress($billingAddress);
    }

    /**
     * @param Shipping $shippingAddress
     * @param PaymentLinkRequestBuilder $builder
     *
     * @return void
     */
    private function setDeliveryAddressForPaymentLink(
        Shipping $shippingAddress,
        PaymentLinkRequestBuilder $builder
    ): void {
        $shippingAddress = new DeliveryAddress(
            $shippingAddress->getCity() ?? '',
            $shippingAddress->getCountry()->getIso() ?? '',
            '',
            $shippingAddress->getZipCode() ?? '',
            '',
            $shippingAddress->getStreet() ?? ''
        );

        $builder->setDeliveryAddress($shippingAddress);
    }
}
