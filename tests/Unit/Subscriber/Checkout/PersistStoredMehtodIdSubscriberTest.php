<?php

declare(strict_types=1);

namespace AdyenPayment\Tests\Unit\Subscriber\Checkout;

use AdyenPayment\AdyenPayment;
use AdyenPayment\Subscriber\Checkout\PersistStoredMethodIdSubscriber;
use AdyenPayment\Tests\Unit\Subscriber\SubscriberTestCase;
use Enlight\Event\SubscriberInterface;
use Enlight_Components_Session_Namespace;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use sAdmin;
use Shopware_Components_Modules;

final class PersistStoredMehtodIdSubscriberTest extends SubscriberTestCase
{
    use ProphecyTrait;

    /** @var PersistStoredMethodIdSubscriber */
    private $subscriber;

    /** @var Enlight_Components_Session_Namespace|ObjectProphecy */
    private $session;

    /** @var ObjectProphecy|Shopware_Components_Modules */
    private $modules;

    protected function setUp(): void
    {
        $this->session = $this->prophesize(Enlight_Components_Session_Namespace::class);
        $this->modules = $this->prophesize(Shopware_Components_Modules::class);
        $this->modules->Admin()->willReturn($this->prophesize(sAdmin::class));
        $this->subscriber = new PersistStoredMethodIdSubscriber($this->session->reveal(), $this->modules->reveal());
    }

    /** @test */
    public function it_is_a_subscriber(): void
    {
        self::assertInstanceOf(SubscriberInterface::class, $this->subscriber);
    }

    /** @test */
    public function it_subscribe_to_the_proper_events(): void
    {
        self::assertEquals(
            ['Enlight_Controller_Action_PostDispatch_Frontend_Checkout' => '__invoke'],
            PersistStoredMethodIdSubscriber::getSubscribedEvents()
        );
    }

    /** @test */
    public function it_does_nothing_on_wrong_request_action_name(): void
    {
        $eventArgs = $this->buildEventArgs('', $viewData = []);
        $eventArgs->getRequest()->setParam('isXHR', true);

        $this->session->set(Argument::cetera())->shouldNotBeCalled();

        $this->subscriber->__invoke($eventArgs);
    }

    /** @test */
    public function it_does_nothing_on_shipping_payment_non_xhr_request(): void
    {
        $eventArgs = $this->buildEventArgs('shippingPayment', $viewData = []);
        $eventArgs->getRequest()->setParam('isXHR', false);

        $this->session->set(Argument::cetera())->shouldNotBeCalled();

        $this->subscriber->__invoke($eventArgs);
    }

    /** @test */
    public function it_saves_in_session_the_stored_method_id_on_shipping_payment_xhr_request(): void
    {
        $eventArgs = $this->buildEventArgs('shippingPayment', $viewData = []);
        $eventArgs->getRequest()->setParam('isXHR', true);
        $eventArgs->getRequest()->setParam(AdyenPayment::SESSION_ADYEN_STORED_METHOD_ID, $storedMethodId = '123123');

        $this->session->offsetSet(AdyenPayment::SESSION_ADYEN_STORED_METHOD_ID, $storedMethodId)->shouldBeCalled();

        $this->subscriber->__invoke($eventArgs);
    }

    /** @test */
    public function it_saves_in_session_the_stored_method_id_on_save_shipping_payment(): void
    {
        $eventArgs = $this->buildEventArgs('saveShippingPayment', $viewData = []);
        $eventArgs->getRequest()->setParam('isXHR', false);
        $eventArgs->getRequest()->setParam(AdyenPayment::SESSION_ADYEN_STORED_METHOD_ID, $storedMethodId = '123123');

        $this->session->offsetSet(AdyenPayment::SESSION_ADYEN_STORED_METHOD_ID, $storedMethodId)->shouldBeCalled();

        $this->subscriber->__invoke($eventArgs);
    }
}
