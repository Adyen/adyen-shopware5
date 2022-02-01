<?php

declare(strict_types=1);

namespace AdyenPayment\Tests\Unit\Subscriber\Checkout;

use AdyenPayment\AdyenPayment;
use AdyenPayment\Shopware\Provider\PaymentMeansProviderInterface;
use AdyenPayment\Subscriber\Checkout\EnrichUmbrellaPaymentMeanSubscriber;
use AdyenPayment\Tests\Unit\Subscriber\SubscriberTestCase;
use Enlight\Event\SubscriberInterface;
use Enlight_Components_Session_Namespace;
use Prophecy\Prophecy\ObjectProphecy;

final class EnrichUmbrellaPaymentMeanSubscriberTest extends SubscriberTestCase
{
    private EnrichUmbrellaPaymentMeanSubscriber $subscriber;

    /** @var Enlight_Components_Session_Namespace|ObjectProphecy */
    private $session;

    /** @var ObjectProphecy|PaymentMeansProviderInterface */
    private $paymentMeansProvider;

    protected function setUp(): void
    {
        $this->session = $this->prophesize(Enlight_Components_Session_Namespace::class);
        $this->paymentMeansProvider = $this->prophesize(PaymentMeansProviderInterface::class);
        $this->subscriber = new EnrichUmbrellaPaymentMeanSubscriber(
            $this->session->reveal(),
            $this->paymentMeansProvider->reveal()
        );
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
            EnrichUmbrellaPaymentMeanSubscriber::getSubscribedEvents()
        );
    }

    /** @test */
    public function it_does_nothing_on_wrong_request_action_name(): void
    {
        $eventArgs = $this->buildEventArgs('', $viewData = ['data' => 'view-data']);

        $this->subscriber->__invoke($eventArgs);
        $this->assertEquals($viewData, $eventArgs->getSubject()->View()->getAssign());
    }

    /** @test */
    public function it_does_nothing_on_xhr_request(): void
    {
        $eventArgs = $this->buildEventArgs('shippingPayment', $viewData = ['data' => 'view-data']);
        $eventArgs->getRequest()->setParam('isXHR', true);

        $this->subscriber->__invoke($eventArgs);
        $this->assertEquals($viewData, $eventArgs->getSubject()->View()->getAssign());
    }

    /** @test */
    public function it_does_nothing_on_missing_session_stored_method_id(): void
    {
        $eventArgs = $this->buildEventArgs('shippingPayment', $viewData = ['data' => 'view-data']);
        $eventArgs->getRequest()->setParam('isXHR', false);

        $this->paymentMeansProvider->__invoke()->willReturn([]);
        $this->session->get(AdyenPayment::SESSION_ADYEN_STORED_METHOD_ID)->willReturn(null);

        $this->subscriber->__invoke($eventArgs);
        $this->assertEquals($viewData, $eventArgs->getSubject()->View()->getAssign());
    }

    /** @test */
    public function it_does_nothing_on_missing_payment_mean_for_stored_method(): void
    {
        $eventArgs = $this->buildEventArgs('shippingPayment', $viewData = ['data' => 'view-data']);
        $eventArgs->getRequest()->setParam('isXHR', false);

        $this->paymentMeansProvider->__invoke()->willReturn([]);
        $this->session->get(AdyenPayment::SESSION_ADYEN_STORED_METHOD_ID)->willReturn($storedMethodId = 'method-id');
        $this->subscriber->__invoke($eventArgs);
        $this->assertEquals($viewData, $eventArgs->getSubject()->View()->getAssign());
    }

    /** @test */
    public function it_will_enrich_the_payment_mean_for_stored_method(): void
    {
        $eventArgs = $this->buildEventArgs('shippingPayment', $viewData = [
            'sUserData' => ['additional' => ['payment' => ['not-enriched-payment-data']]],
            'sFormData' => [],
        ]);
        $eventArgs->getRequest()->setParam('isXHR', false);

        $this->session->get(AdyenPayment::SESSION_ADYEN_STORED_METHOD_ID)->willReturn($storedMethodId = 'method-id');
        $this->paymentMeansProvider->__invoke()->willReturn([$paymentMeanRaw = [
            'source' => 123,
            'adyenType' => 'test',
            'stored_method_id' => $storedMethodId,
            'stored_method_umbrella_id' => $umbrellaId = 'umbrella-id',
        ]]);

        $this->subscriber->__invoke($eventArgs);

        $expected = [
            'sUserData' => ['additional' => ['payment' => $paymentMeanRaw]],
            'sFormData' => ['payment' => $umbrellaId],
        ];

        $this->assertEquals($expected, $eventArgs->getSubject()->View()->getAssign());
    }
}
