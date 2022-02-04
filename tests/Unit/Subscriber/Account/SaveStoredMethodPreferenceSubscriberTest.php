<?php

declare(strict_types=1);

namespace AdyenPayment\Tests\Unit\Subscriber\Checkout;

use AdyenPayment\Components\Adyen\PaymentMethod\StoredPaymentMeanProviderInterface;
use AdyenPayment\Components\Manager\UserPreferenceManagerInterface;
use AdyenPayment\Models\UserPreference;
use AdyenPayment\Subscriber\Account\SaveStoredMethodPreferenceSubscriber;
use Doctrine\ORM\EntityRepository;
use Enlight\Event\SubscriberInterface;
use Enlight_Components_Session_Namespace;
use Enlight_Controller_ActionEventArgs;
use Enlight_Controller_Request_Request;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;

final class SaveStoredMethodPreferenceSubscriberTest extends TestCase
{
    use ProphecyTrait;
    private SaveStoredMethodPreferenceSubscriber $subscriber;

    /** @var Enlight_Components_Session_Namespace|ObjectProphecy */
    private $session;

    /** @var ObjectProphecy|UserPreferenceManagerInterface */
    private $userPreferenceManager;

    /** @var EntityRepository|ObjectProphecy */
    private $userPreferenceRepository;

    /** @var StoredPaymentMeanProviderInterface */
    private $storedPaymentMeanProvider;

    /** @var Enlight_Controller_ActionEventArgs|ObjectProphecy */
    private $args;

    /** @var Enlight_Controller_Request_Request|ObjectProphecy */
    private $request;

    protected function setUp(): void
    {
        $this->args = $this->prophesize(Enlight_Controller_ActionEventArgs::class);
        $this->request = $this->prophesize(Enlight_Controller_Request_Request::class);
        $this->session = $this->prophesize(Enlight_Components_Session_Namespace::class);
        $this->userPreferenceManager = $this->prophesize(UserPreferenceManagerInterface::class);
        $this->userPreferenceRepository = $this->prophesize(EntityRepository::class);
        $this->storedPaymentMeanProvider = $this->prophesize(StoredPaymentMeanProviderInterface::class);

        $this->subscriber = new SaveStoredMethodPreferenceSubscriber(
            $this->session->reveal(),
            $this->userPreferenceManager->reveal(),
            $this->userPreferenceRepository->reveal(),
            $this->storedPaymentMeanProvider->reveal()
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
            ['Enlight_Controller_Action_PostDispatch_Frontend_Account' => '__invoke'],
            SaveStoredMethodPreferenceSubscriber::getSubscribedEvents()
        );
    }

    /** @test */
    public function it_does_nothing_on_missing_user_id(): void
    {
        $this->session->get('sUserId')->willReturn(null);
        $this->request->getActionName()->shouldNotBeCalled();
        $this->request->isPost()->shouldNotBeCalled();
        $this->args->getRequest()->shouldNotBeCalled();
        $this->storedPaymentMeanProvider->fromRequest(Argument::cetera())->shouldNotBeCalled();

        $this->subscriber->__invoke($this->args->reveal());
    }

    /** @test */
    public function it_does_nothing_on_wrong_request_action_name(): void
    {
        $this->session->get('sUserId')->willReturn(123456);
        $this->request->getActionName()->willReturn('wrong-action-name');
        $this->request->isPost()->willReturn(true);
        $this->args->getRequest()->willReturn($this->request);
        $this->storedPaymentMeanProvider->fromRequest(Argument::cetera())->shouldNotBeCalled();

        $this->subscriber->__invoke($this->args->reveal());
    }

    /** @test */
    public function it_does_nothing_on_wrong_request_method(): void
    {
        $this->session->get('sUserId')->willReturn(123456);
        $this->request->getActionName()->willReturn('savePayment');
        $this->request->isPost()->willReturn(false);
        $this->args->getRequest()->willReturn($this->request);
        $this->storedPaymentMeanProvider->fromRequest(Argument::cetera())->shouldNotBeCalled();

        $this->subscriber->__invoke($this->args->reveal());
    }

    /** @test */
    public function it_will_save_the_user_preferences_with_empty_params(): void
    {
        $this->session->get('sUserId')->willReturn($userId = 123456);
        $this->request->getActionName()->willReturn('savePayment');
        $this->request->isPost()->willReturn(true);
        $this->request->getParam('register', [])->willReturn([]);
        $this->args->getRequest()->willReturn($this->request->reveal());
        $this->storedPaymentMeanProvider->fromRequest($this->request->reveal())->willReturn(null);

        $userPreference = new UserPreference();
        $userPreference->setUserId($userId);
        $userPreference->setStoredMethodId(null);

        $this->userPreferenceManager->save($userPreference)->shouldBeCalled();

        $this->subscriber->__invoke($this->args->reveal());
    }

    /** @test */
    public function it_will_save_the_user_preferences_with_null_for_none_stored_method_param(): void
    {
        $this->session->get('sUserId')->willReturn($userId = 123456);
        $this->request->getActionName()->willReturn('savePayment');
        $this->request->isPost()->willReturn(true);
        $this->request->getParam('register', [])->willReturn(['payment' => 'noneStoredPaymentId']);
        $this->args->getRequest()->willReturn($this->request->reveal());
        $this->storedPaymentMeanProvider->fromRequest($this->request->reveal())->willReturn(null);

        $userPreference = new UserPreference();
        $userPreference->setUserId($userId);
        $userPreference->setStoredMethodId(null);

        $this->userPreferenceManager->save($userPreference)->shouldBeCalled();

        $this->subscriber->__invoke($this->args->reveal());
    }

    /** @test */
    public function it_will_save_the_user_preferences_with_param_value(): void
    {
        $this->session->get('sUserId')->willReturn($userId = 123456);
        $this->request->getActionName()->willReturn('savePayment');
        $this->request->isPost()->willReturn(true);
        $this->request->getParam('register', [])->willReturn([
            'payment' => 'proper_'.($storedMethodId = 'storedMethodId'),
        ]);
        $this->args->getRequest()->willReturn($this->request->reveal());
        $this->storedPaymentMeanProvider->fromRequest($this->request->reveal())->willReturn($storedMethodId);

        $userPreference = new UserPreference();
        $userPreference->setUserId($userId);
        $userPreference->setStoredMethodId($storedMethodId);

        $this->userPreferenceManager->save($userPreference)->shouldBeCalled();

        $this->subscriber->__invoke($this->args->reveal());
    }

    /** @test */
    public function it_will_update_the_user_preferences_with_param_value(): void
    {
        $this->session->get('sUserId')->willReturn($userId = 123456);
        $this->request->getActionName()->willReturn('savePayment');
        $this->request->isPost()->willReturn(true);
        $this->request->getParam('register', [])->willReturn([
            'payment' => 'proper_'.($storedMethodId = 'storedMethodId'),
        ]);
        $this->args->getRequest()->willReturn($this->request->reveal());
        $this->storedPaymentMeanProvider->fromRequest($this->request->reveal())->willReturn($storedMethodId);

        $userPreference = new UserPreference();
        $userPreference->setId(123);
        $userPreference->setUserId($userId);
        $userPreference->setStoredMethodId($storedMethodId);

        $this->userPreferenceRepository->findOneBy(['userId' => $userId])->willReturn($userPreference);

        $this->userPreferenceManager->save($userPreference)->shouldBeCalled();

        $this->subscriber->__invoke($this->args->reveal());
    }
}
