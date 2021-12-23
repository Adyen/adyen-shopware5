<?php

declare(strict_types=1);

namespace AdyenPayment\Tests\Unit\Shopware\Plugin;

use AdyenPayment\AdyenPayment;
use AdyenPayment\Subscriber\Backend\HideStoredPaymentUmbrellaSubscriber;
use Enlight\Event\SubscriberInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Component\HttpFoundation\Response;

final class HideStoredPaymentUmbrellaSubscriberTest extends TestCase
{
    use ProphecyTrait;
    private HideStoredPaymentUmbrellaSubscriber $subscriber;

    /** @var \Enlight_Controller_ActionEventArgs|ObjectProphecy */
    private $args;

    /** @var \Enlight_Controller_Request_Request|ObjectProphecy */
    private $request;

    /** @var \Enlight_Controller_Response_Response|ObjectProphecy */
    private $response;

    protected function setUp(): void
    {
        $this->args = $this->prophesize(\Enlight_Controller_ActionEventArgs::class);
        $this->request = $this->prophesize(\Enlight_Controller_Request_Request::class);
        $this->response = $this->prophesize(\Enlight_Controller_Response_Response::class);

        $this->subscriber = new HideStoredPaymentUmbrellaSubscriber();
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
            ['Enlight_Controller_Action_PostDispatch_Backend_Payment' => '__invoke'],
            HideStoredPaymentUmbrellaSubscriber::getSubscribedEvents()
        );
    }

    /** @test */
    public function it_does_nothing_on_missing_request(): void
    {
        $this->args->get('request')->willReturn(false);
        $this->args->get('response')->willReturn($this->response->reveal());
        $this->response->getHttpResponseCode()->shouldNotBeCalled();
        $this->request->getActionName()->shouldNotBeCalled();

        ($this->subscriber)($this->args->reveal());
    }

    /** @test */
    public function it_does_nothing_on_missing_response(): void
    {
        $this->args->get('request')->willReturn($this->request->reveal());
        $this->args->get('response')->willReturn(false);
        $this->response->getHttpResponseCode()->shouldNotBeCalled();
        $this->request->getActionName()->shouldNotBeCalled();

        ($this->subscriber)($this->args->reveal());
    }

    /** @test */
    public function it_does_nothing_on_wrong_response_code(): void
    {
        $this->args->get('request')->willReturn($this->request->reveal());
        $this->args->get('response')->willReturn($this->response->reveal());
        $this->response->getHttpResponseCode()->willReturn('any-code');
        $this->request->getActionName()->shouldNotBeCalled();

        ($this->subscriber)($this->args->reveal());
    }

    /** @test */
    public function it_does_nothing_on_wrong_request_action_name(): void
    {
        $this->args->get('request')->willReturn($this->request->reveal());
        $this->args->get('response')->willReturn($this->response->reveal());
        $this->response->getHttpResponseCode()->willReturn(Response::HTTP_OK);
        $this->request->getActionName()->willReturn('invalid-action-name');
        $this->args->getSubject()->shouldNotBeCalled();

        ($this->subscriber)($this->args->reveal());
    }

    /** @test */
    public function it_does_nothing_on_wrong_data(): void
    {
        $view = $this->prophesize(\Enlight_View_Default::class);
        $view->getAssign()->willReturn([]); // $assign['data'] undefined
        $subject = $this->prophesize(\Enlight_Controller_Action::class);
        $subject->View()->willReturn($view->reveal());

        $this->args->get('request')->willReturn($this->request->reveal());
        $this->args->get('response')->willReturn($this->response->reveal());
        $this->response->getHttpResponseCode()->willReturn(Response::HTTP_OK);
        $this->request->getActionName()->willReturn('getPayments');
        $this->args->getSubject()->willReturn($subject->reveal());

        ($this->subscriber)($this->args->reveal());
    }

    /** @test */
    public function it_will_filter_from_the_view_the_stored_payment_umbrella(): void
    {
        $expected = [
            ['name' => 'Payment Method 1'],
            ['name' => 'Payment Method 2'],
        ];
        $rawMethods = ['data' => array_merge(
            [['name' => AdyenPayment::ADYEN_STORED_PAYMENT_UMBRELLA_CODE]],
            $expected
        )];

        $view = $this->prophesize(\Enlight_View_Default::class);
        $view->getAssign()->willReturn($rawMethods);
        $subject = $this->prophesize(\Enlight_Controller_Action::class);
        $subject->View()->willReturn($view->reveal());

        $this->args->get('request')->willReturn($this->request->reveal());
        $this->args->get('response')->willReturn($this->response->reveal());
        $this->response->getHttpResponseCode()->willReturn(Response::HTTP_OK);
        $this->request->getActionName()->willReturn('getPayments');
        $this->args->getSubject()->willReturn($subject->reveal());
        $view->assign(['success' => true, 'data' => $expected])->shouldBeCalled();

        ($this->subscriber)($this->args->reveal());
    }
}
