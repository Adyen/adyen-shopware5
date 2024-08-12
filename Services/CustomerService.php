<?php

namespace AdyenPayment\Services;

use Doctrine\ORM\Exception\NotSupported;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;
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
     * @throws NotSupported
     */
    public function getCustomerByEmail($email)
    {
        return $this->modelManager->getRepository(Customer::class)->findOneBy(['email' => $email]);
    }

    /**
     * Creates a new customer.
     *
     * @param $email
     * @param $sourceAddress
     *
     * @return Customer
     *
     * @throws NotSupported
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function createCustomer($email, $sourceAddress)
    {
        $customer = new Customer();
        $customer->setEmail($email);
        $customer->setPassword('secure_password');
        $customer->setActive(true);

        $address = new Address();
        $address->setFirstName($sourceAddress->firstName);
        $address->setLastName($sourceAddress->lastName);
        $address->setStreet($sourceAddress->street);
        $address->setZipCode($sourceAddress->zipCode);
        $address->setPhone($sourceAddress->phone);
        $address->setCity($sourceAddress->city);
        $address->setCountry($this->modelManager->getRepository(Country::class)->findOneBy(['iso' => $sourceAddress->country]));

        $customer->setDefaultBillingAddress($address);
        $customer->setDefaultShippingAddress($address);
        $address->setCustomer($customer);

        // Set customer group (default group here)
        $customerGroup = $this->modelManager->getRepository(Group::class)->findOneBy(['key' => 'EK']);
        $customer->setGroup($customerGroup);

        $this->modelManager->persist($customer);
        $this->modelManager->persist($address);
        $this->modelManager->flush();

        return $customer;
    }
}
