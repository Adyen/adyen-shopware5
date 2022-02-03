<?php

declare(strict_types=1);

namespace AdyenPayment\Tests\Unit\Subscriber\Checkout;

use AdyenPayment\AdyenPayment;
use AdyenPayment\Collection\Payment\PaymentMeanCollection;
use AdyenPayment\Components\Adyen\PaymentMethod\EnrichedPaymentMeanProviderInterface;
use AdyenPayment\Shopware\Provider\PaymentMeansProviderInterface;
use AdyenPayment\Subscriber\Checkout\EnrichUserAdditionalPaymentSubscriber;
use AdyenPayment\Tests\Unit\Subscriber\SubscriberTestCase;
use Enlight\Event\SubscriberInterface;
use Enlight_Components_Session_Namespace;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;

final class EnrichUserAdditionalPaymentSubscriberTest extends SubscriberTestCase
{
    use ProphecyTrait;
    private EnrichUserAdditionalPaymentSubscriber $subscriber;

    /** @var EnrichedPaymentMeanProviderInterface|ObjectProphecy */
    private $enrichedPaymentMeanProvider;

    /** @var ObjectProphecy|PaymentMeansProviderInterface */
    private $paymentMeansProvider;

    /** @var Enlight_Components_Session_Namespace|ObjectProphecy */
    private $session;

    protected function setUp(): void
    {
        $this->enrichedPaymentMeanProvider = $this->prophesize(EnrichedPaymentMeanProviderInterface::class);
        $this->paymentMeansProvider = $this->prophesize(PaymentMeansProviderInterface::class);
        $this->session = $this->prophesize(Enlight_Components_Session_Namespace::class);
        $this->subscriber = new EnrichUserAdditionalPaymentSubscriber(
            $this->enrichedPaymentMeanProvider->reveal(),
            $this->paymentMeansProvider->reveal(),
            $this->session->reveal()
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
            ['Enlight_Controller_Action_PostDispatch_Frontend_Checkout' => ['__invoke', -99999]],
            EnrichUserAdditionalPaymentSubscriber::getSubscribedEvents()
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
    public function it_does_nothing_on_missing_stored_method_id_and_payment_mean_id(): void
    {
        $eventArgs = $this->buildEventArgs('confirm', $viewData = ['sUserData' => []]);

        $this->session->get(AdyenPayment::SESSION_ADYEN_STORED_METHOD_ID)->willReturn(null);

        $this->subscriber->__invoke($eventArgs);
        $this->assertEquals($viewData, $eventArgs->getSubject()->View()->getAssign());
    }

    /** @test */
    public function it_does_nothing_on_missing_payment_mean_for_stored_method_id(): void
    {
        $eventArgs = $this->buildEventArgs('confirm', $viewData = ['sUserData' => []]);

        $this->session->get(AdyenPayment::SESSION_ADYEN_STORED_METHOD_ID)->willReturn('method-id');
        $this->paymentMeansProvider->__invoke()->willReturn($paymentMeansRaw = [[
            'source' => 123,
            'adyenType' => 'test',
        ]]);

        $paymentMeans = PaymentMeanCollection::createFromShopwareArray($paymentMeansRaw);
        $this->enrichedPaymentMeanProvider->__invoke($paymentMeans)->willReturn($paymentMeans);

        $this->subscriber->__invoke($eventArgs);

        $this->assertEquals($viewData, $eventArgs->getSubject()->View()->getAssign());
    }

    /** @test */
    public function it_does_nothing_on_missing_payment_mean_for_payment_id(): void
    {
        $eventArgs = $this->buildEventArgs('confirm', $viewData = ['sUserData' => [
            'additional' => ['payment' => ['id' => $paymentId = '123123']],
        ]]);

        $this->session->get(AdyenPayment::SESSION_ADYEN_STORED_METHOD_ID)->willReturn(null);
        $this->paymentMeansProvider->__invoke()->willReturn($paymentMeansRaw = [[
            'source' => 123,
            'adyenType' => 'test',
        ]]);

        $paymentMeans = PaymentMeanCollection::createFromShopwareArray($paymentMeansRaw);
        $this->enrichedPaymentMeanProvider->__invoke($paymentMeans)->willReturn($paymentMeans);

        $this->subscriber->__invoke($eventArgs);

        $this->assertEquals($viewData, $eventArgs->getSubject()->View()->getAssign());
    }

    /** @test */
    public function it_will_update_the_view_with_payment_mean_for_stored_method_id(): void
    {
        $eventArgs = $this->buildEventArgs('confirm', $viewData = ['sUserData' => []]);

        $this->session->get(AdyenPayment::SESSION_ADYEN_STORED_METHOD_ID)->willReturn($storedMethodId = 'method-id');
        $this->paymentMeansProvider->__invoke()->willReturn($paymentMeansRaw = [$paymentMeanRaw = [
            'source' => 123,
            'adyenType' => 'test',
            'stored_method_id' => $storedMethodId,
        ]]);

        $paymentMeans = PaymentMeanCollection::createFromShopwareArray($paymentMeansRaw);
        $this->enrichedPaymentMeanProvider->__invoke($paymentMeans)->willReturn($paymentMeans);

        $this->subscriber->__invoke($eventArgs);

        $expected = [
            'sUserData' => ['additional' => ['payment' => $paymentMeanRaw]],
        ];

        $this->assertEquals($expected, $eventArgs->getSubject()->View()->getAssign());
    }

    /** @test */
    public function it_will_update_the_view_with_payment_mean_for_payment_id(): void
    {
        $eventArgs = $this->buildEventArgs('confirm', $viewData = ['sUserData' => [
            'additional' => ['payment' => ['id' => $paymentId = '123123']],
        ]]);

        $this->session->get(AdyenPayment::SESSION_ADYEN_STORED_METHOD_ID)->willReturn(null);
        $this->paymentMeansProvider->__invoke()->willReturn($paymentMeansRaw = [$paymentMeanRaw = [
            'id' => $paymentId,
            'source' => 123,
            'adyenType' => 'test',
        ]]);

        $paymentMeans = PaymentMeanCollection::createFromShopwareArray($paymentMeansRaw);
        $this->enrichedPaymentMeanProvider->__invoke($paymentMeans)->willReturn($paymentMeans);

        $this->subscriber->__invoke($eventArgs);

        $expected = [
            'sUserData' => ['additional' => ['payment' => $paymentMeanRaw]],
        ];

        $this->assertEquals($expected, $eventArgs->getSubject()->View()->getAssign());
    }
}
