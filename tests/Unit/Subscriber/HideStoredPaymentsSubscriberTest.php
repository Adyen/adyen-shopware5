<?php

declare(strict_types=1);

namespace AdyenPayment\Tests\Unit\Shopware\Plugin;

use AdyenPayment\Models\Enum\PaymentMethod\SourceType;
use AdyenPayment\Subscriber\Backend\HideStoredPaymentsSubscriber;
use Enlight\Event\SubscriberInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Component\HttpFoundation\Response;

final class HideStoredPaymentsSubscriberTest extends TestCase
{
    use ProphecyTrait;
    private HideStoredPaymentsSubscriber $subscriber;

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
        $this->args->get('request')->willReturn(false);
        $this->args->get('response')->willReturn($this->response->reveal());
        $this->args->getSubject()->shouldNotBeCalled();
        $this->response->getHttpResponseCode()->shouldNotBeCalled();
        $this->request->getActionName()->shouldNotBeCalled();

        ($this->subscriber)($this->args->reveal());
    }

    /** @test */
    public function it_does_nothing_on_missing_response(): void
    {
        $this->args->get('request')->willReturn($this->request->reveal());
        $this->args->get('response')->willReturn(false);
        $this->args->getSubject()->shouldNotBeCalled();
        $this->response->getHttpResponseCode()->shouldNotBeCalled();
        $this->request->getActionName()->shouldNotBeCalled();

        ($this->subscriber)($this->args->reveal());
    }

    /** @test */
    public function it_does_nothing_on_wrong_response_code(): void
    {
        $this->args->get('request')->willReturn($this->request->reveal());
        $this->args->get('response')->willReturn($this->response->reveal());
        $this->args->getSubject()->shouldNotBeCalled();
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
        $view->getAssign()->willReturn([]);
        $subject = $this->prophesize(\Enlight_Controller_Action::class);
        $subject->View()->willReturn($view->reveal());

        $this->args->get('request')->willReturn($this->request->reveal());
        $this->args->get('response')->willReturn($this->response->reveal());
        $this->response->getHttpResponseCode()->willReturn(Response::HTTP_OK);
        $this->request->getActionName()->willReturn('getPayments');
        $this->args->getSubject()->willReturn($subject->reveal());
        $view->assign(Argument::cetera())->shouldNotBeCalled();

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
            [['hide' => 1, 'source' => SourceType::adyen()->getType()]],
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
        $view->assign('data', $expected)->shouldBeCalled();

        ($this->subscriber)($this->args->reveal());
    }
}
