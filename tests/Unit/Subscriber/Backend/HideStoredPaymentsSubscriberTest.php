<?php

declare(strict_types=1);

namespace AdyenPayment\Tests\Unit\Subscriber\Backend;

use AdyenPayment\Models\Enum\PaymentMethod\SourceType;
use AdyenPayment\Subscriber\Backend\HideStoredPaymentsSubscriber;
use AdyenPayment\Tests\Unit\Subscriber\SubscriberTestCase;
use Enlight\Event\SubscriberInterface;
use Symfony\Component\HttpFoundation\Response;

final class HideStoredPaymentsSubscriberTest extends SubscriberTestCase
{
    private HideStoredPaymentsSubscriber $subscriber;

    protected function setUp(): void
    {
        $this->subscriber = new HideStoredPaymentsSubscriber();
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
            [
                'Enlight_Controller_Action_PostDispatchSecure_Backend_Payment' => '__invoke',
                'Enlight_Controller_Action_PostDispatchSecure_Backend_Shipping' => '__invoke',
            ],
            HideStoredPaymentsSubscriber::getSubscribedEvents()
        );
    }

    /** @test */
    public function it_does_nothing_on_missing_request(): void
    {
        $eventArgs = new \Enlight_Controller_ActionEventArgs([
            'subject' => $this->buildSubject($viewData = ['data' => 'view-data']),
            'request' => null,
            'response' => new \Enlight_Controller_Response_ResponseTestCase(),
        ]);

        $this->subscriber->__invoke($eventArgs);
        $this->assertEquals($viewData, $eventArgs->getSubject()->View()->getAssign());
    }

    /** @test */
    public function it_does_nothing_on_missing_response(): void
    {
        $eventArgs = new \Enlight_Controller_ActionEventArgs([
            'subject' => $this->buildSubject($viewData = ['data' => 'view-data']),
            'request' => new \Enlight_Controller_Request_RequestTestCase(),
            'response' => null,
        ]);

        $this->subscriber->__invoke($eventArgs);
        $this->assertEquals($viewData, $eventArgs->getSubject()->View()->getAssign());
    }

    /** @test */
    public function it_does_nothing_on_non_success_response_code(): void
    {
        $eventArgs = $this->buildEventArgs('', $viewData = ['data' => 'view-data'], Response::HTTP_BAD_REQUEST);

        $this->subscriber->__invoke($eventArgs);
        $this->assertEquals($viewData, $eventArgs->getSubject()->View()->getAssign());
    }

    /** @test */
    public function it_does_nothing_on_wrong_request_action_name(): void
    {
        $eventArgs = $this->buildEventArgs('', $viewData = ['data' => 'view-data']);

        $this->subscriber->__invoke($eventArgs);
        $this->assertEquals($viewData, $eventArgs->getSubject()->View()->getAssign());
    }

    /** @test */
    public function it_does_nothing_on_empty_view_data(): void
    {
        $eventArgs = $this->buildEventArgs('getPayments', $viewData = []);

        $this->subscriber->__invoke($eventArgs);
        $this->assertEquals($viewData, $eventArgs->getSubject()->View()->getAssign());
    }

    /** @test */
    public function it_filters_hidden_payment_means(): void
    {
        $viewData = [
            'data' => [
                $nonHiddenPaymentMean = [
                    'name' => 'A payment mean not hidden',
                    'source' => SourceType::shopwareDefault()->getType(),
                    'hide' => false,
                ],
                [
                    'name' => 'A hidden payment mean',
                    'source' => SourceType::shopwareDefault()->getType(),
                    'hide' => true,
                ],
            ],
        ];
        $eventArgs = $this->buildEventArgs('getPayments', $viewData);

        $this->subscriber->__invoke($eventArgs);
        $this->assertEquals([
            'data' => [
                $nonHiddenPaymentMean,
            ],
        ], $eventArgs->getSubject()->View()->getAssign());
    }
}
