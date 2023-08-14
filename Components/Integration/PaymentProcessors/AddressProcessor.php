<?php

namespace AdyenPayment\Components\Integration\PaymentProcessors;

use Adyen\Core\BusinessLogic\Domain\Checkout\PaymentRequest\Factory\PaymentRequestBuilder;
use Adyen\Core\BusinessLogic\Domain\Checkout\PaymentRequest\Models\BillingAddress;
use Adyen\Core\BusinessLogic\Domain\Checkout\PaymentRequest\Models\DeliveryAddress;
use Adyen\Core\BusinessLogic\Domain\Checkout\PaymentRequest\Models\StartTransactionRequestContext;
use Adyen\Core\BusinessLogic\Domain\Integration\Processors\AddressProcessor as BaseAddressProcessor;
use Shopware\Models\Country\Country;
use Shopware\Models\Country\Repository;

/**
 * Class AddressProcessor
 *
 * @package AdyenPayment\Components\Integration\PaymentProcessors
 */
class AddressProcessor implements BaseAddressProcessor
{
    /**
     * @var Repository
     */
    private $countryRepository;

    /**
     * @param Repository $countryRepository
     */
    public function __construct(Repository $countryRepository)
    {
        $this->countryRepository = $countryRepository;
    }

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
            $country = $this->countryRepository->getCountryQuery($userData['shippingaddress']['countryId'])->getResult();
            $state = null;

            if (!empty($userData['shippingaddress']['stateID'])) {
                $state = Shopware()->Models()->getRepository('Shopware\Models\Country\State')->findOneBy(['id' => $userData['shippingaddress']['stateID']]);
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

    private function setBillingAddress(
        ?array                $billingAddressRawData,
        ?Country              $country,
        array                 $userData,
        PaymentRequestBuilder $builder
    ): void
    {
        if (!empty($billingAddressRawData)) {
            return;
        }

        $billingAddress = new BillingAddress(
            $userData['billingaddress']['city'] ?? '',
            $country ? $country->getIso() : '',
            '',
            $userData['billingaddress']['zipcode'] ?? '',
            '',
            $userData['billingaddress']['street'] ?? ''
        );

        $builder->setBillingAddress($billingAddress);
    }

    private function setCountryCode(?array $stateDataCountry, ?Country $country, PaymentRequestBuilder $builder): void
    {
        if (!empty($stateDataCountry)) {
            return;
        }

        $builder->setCountryCode($country ? $country->getIso() : '');
    }
}
