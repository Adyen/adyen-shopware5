<?php

declare(strict_types=1);

namespace Unit\Shopware\Provider;

use AdyenPayment\Shopware\Provider\CustomerNumberProvider;
use AdyenPayment\Shopware\Provider\CustomerNumberProviderInterface;
use Doctrine\ORM\EntityRepository;
use Enlight_Components_Session_Namespace;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Shopware\Components\Model\ModelManager;
use Shopware\Models\Customer\Customer;

class CustomerNumberProviderTest extends TestCase
{
    use ProphecyTrait;

    /** @var Enlight_Components_Session_Namespace|ObjectProphecy */
    private $session;

    /** @var ModelManager|ObjectProphecy */
    private $modelManager;
    private CustomerNumberProvider $customerNumberProvider;

    protected function setUp(): void
    {
        $this->session = $this->prophesize(Enlight_Components_Session_Namespace::class);
        $this->modelManager = $this->prophesize(ModelManager::class);
        $this->customerNumberProvider = new CustomerNumberProvider(
            $this->session->reveal(),
            $this->modelManager->reveal()
        );
    }

    /** @test */
    public function it_is_a_customer_number_provider(): void
    {
        $this->assertInstanceOf(CustomerNumberProviderInterface::class, $this->customerNumberProvider);
    }

    /** @test */
    public function it_returns_empty_string_when_no_user_id_in_session(): void
    {
        $this->session->get('sUserId')->shouldBeCalledOnce()->willReturn(null);
        $this->modelManager->getRepository(Customer::class)->shouldNotBeCalled();
        $customerNumber = ($this->customerNumberProvider)();

        $this->assertEquals('', $customerNumber);
    }

    /** @test */
    public function it_returns_empty_string_when_no_customer_returned_from_repository(): void
    {
        $customerRepository = $this->prophesize(EntityRepository::class);

        $this->session->get('sUserId')->shouldBeCalledOnce()->willReturn($userId = '123');
        $this->modelManager->getRepository(Customer::class)
            ->shouldBeCalledOnce()
            ->willReturn($customerRepository->reveal());
        $customerRepository->find($userId)->willReturn(null);

        $customerNumber = ($this->customerNumberProvider)();
        $this->assertEquals('', $customerNumber);
    }

    /** @test */
    public function it_returns_customer_number(): void
    {
        $customer = new Customer();
        $customer->setNumber($customerNumber = 'abc');

        $customerRepository = $this->prophesize(EntityRepository::class);

        $this->session->get('sUserId')->shouldBeCalledOnce()->willReturn($customerNumber);
        $this->modelManager->getRepository(Customer::class)
            ->shouldBeCalledOnce()
            ->willReturn($customerRepository->reveal());
        $customerRepository->find($customerNumber)
            ->willReturn($customer);

        $customerNumberExpected = ($this->customerNumberProvider)();
        $this->assertEquals($customerNumberExpected, $customerNumber);
    }
}
