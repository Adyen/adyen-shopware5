<?php

namespace AdyenPayment\Services;

use Doctrine\ORM\Exception\NotSupported;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;
use Enlight_Controller_Request_RequestHttp;
use http\Exception\InvalidArgumentException;
use Shopware\Components\Model\ModelManager;
use Shopware\Models\Country\Country;
use Shopware\Models\Customer\Address;
use Shopware\Models\Customer\Customer;
use Shopware\Models\Customer\Group;

class CustomerService
{
    private $modelManager;

    public function __construct(ModelManager $modelManager)
    {
        $this->modelManager = $modelManager;
    }

    /**
     * Returns a customer by email.
     *
     * @throws NotSupported
     */
    public function getCustomerByEmail($email)
    {
        return $this->modelManager->getRepository(Customer::class)->findOneBy(['email' => $email]);
    }

    /**
     *  Checks if given county is active.
     *
     * @param string $countryIso
     *
     * @return bool
     */
    public function verifyIfCountryIsActive($countryIso)
    {
        $country = $this->modelManager->getRepository(Country::class)->findOneBy(['iso' => $countryIso]);

        return $country ? $country->getActive() : false;
    }

    /**
     * Checks whether there is a user logged in at the moment.
     *
     * @return bool
     */
    public function isUserLoggedIn(): bool
    {
        if (!(bool)Shopware()->Session()->get('sUserId')) {
            return false;
        }

        $userData = Shopware()->Modules()->Admin()->sGetUserData();
        if (
            !empty($userData['additional']['user']['accountmode']) &&
            (int)$userData['additional']['user']['accountmode'] === Customer::ACCOUNT_MODE_FAST_LOGIN
        ) {
            return false;
        }

        return true;
    }

    /**
     * Initializes a customer on the storefront.
     *
     * @throws OptimisticLockException
     * @throws NotSupported
     * @throws ORMException
     */
    public function initializeCustomer(Enlight_Controller_Request_RequestHttp $request)
    {
        $email = str_replace(['"', "'"], '', $request->getParam('adyenEmail'));
        $sourceBillingAddress = json_decode($request->getParam('adyenBillingAddress'));
        $sourceShippingAddress = json_decode($request->getParam('adyenShippingAddress'));

        $firstName = !empty($sourceBillingAddress->firstName) ? htmlspecialchars($sourceBillingAddress->firstName) : 'Guest';
        $lastName = !empty($sourceBillingAddress->lastName) ? htmlspecialchars($sourceBillingAddress->lastName) : 'Guest';

        if ($email && $sourceBillingAddress && $sourceShippingAddress) {
            $customer = $this->getCustomerByEmail($email);

            if (!$customer) {
                $customer = $this->createCustomer($email, $firstName, $lastName);
            }

            $billingAddress = $this->createAddress($sourceBillingAddress);
            $shippingAddress = $this->createAddress($sourceShippingAddress);
            $customer->setDefaultBillingAddress($billingAddress);
            $customer->setDefaultShippingAddress($shippingAddress);
            $billingAddress->setCustomer($customer);
            $shippingAddress->setCustomer($customer);

            $this->modelManager->persist($billingAddress);
            $this->modelManager->persist($shippingAddress);
            $this->modelManager->persist($customer);
            $this->modelManager->flush();

            return $customer;
        }

        throw new InvalidArgumentException('Required customer information missing from the request.');
    }

    /**
     * Creates a new customer.
     *
     * @param $email
     * @param $sourceBillingAddress
     * @param $sourceShippingAddress
     *
     * @return Customer
     *
     * @throws NotSupported
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function createCustomer($email, $firstName, $lastName)
    {
        $customer = new Customer();
        $customer->setEmail($email);
        $customer->setFirstName($firstName);
        $customer->setLastName($lastName);
        $shop = $this->modelManager->getRepository(\Shopware\Models\Shop\Shop::class)->find(Shopware()->Shop()->getId());
        $customer->setShop($shop);
        $customer->setPassword(md5(time()));
        $customer->setActive(true);

        // Set customer group (default group here)
        $customerGroup = $this->modelManager->getRepository(Group::class)->findOneBy(['key' => 'EK']);
        $customer->setGroup($customerGroup);

        $this->modelManager->persist($customer);
        $this->modelManager->flush();

        return $customer;
    }

    /**
     * Creates a customer address.
     *
     * @param $sourceAddress
     *
     * @return Address
     *
     * @throws NotSupported
     */
    private function createAddress($sourceAddress): Address
    {
        $address = new Address();
        $address->setFirstName($sourceAddress->firstName);
        $address->setLastName($sourceAddress->lastName);
        $address->setStreet($sourceAddress->street);
        $address->setZipCode($sourceAddress->zipCode);
        $address->setPhone($sourceAddress->phone);
        $address->setCity($sourceAddress->city);
        $address->setCountry($this->modelManager->getRepository(Country::class)->findOneBy(['iso' => $sourceAddress->country]));

        $this->modelManager->persist($address);

        return $address;
    }
}
