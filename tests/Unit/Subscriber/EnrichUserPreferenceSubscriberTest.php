<?php

declare(strict_types=1);

namespace AdyenPayment\Tests\Unit\Subscriber\Checkout;

use AdyenPayment\Models\UserPreference;
use AdyenPayment\Subscriber\EnrichUserPreferenceSubscriber;
use AdyenPayment\Tests\Unit\Subscriber\SubscriberTestCase;
use Doctrine\ORM\EntityRepository;
use Enlight\Event\SubscriberInterface;
use Enlight_Components_Session_Namespace;
use Prophecy\Prophecy\ObjectProphecy;

final class EnrichUserPreferenceSubscriberTest extends SubscriberTestCase
{
    private EnrichUserPreferenceSubscriber $subscriber;

    /** @var Enlight_Components_Session_Namespace|ObjectProphecy */
    private $session;

    /** @var EntityRepository|ObjectProphecy */
    private $userPreferenceRepository;

    protected function setUp(): void
    {
        $this->session = $this->prophesize(Enlight_Components_Session_Namespace::class);
        $this->userPreferenceRepository = $this->prophesize(EntityRepository::class);
        $this->subscriber = new EnrichUserPreferenceSubscriber(
            $this->session->reveal(),
            $this->userPreferenceRepository->reveal()
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
            [
                // inject in the view as early as possible to get the info in the other subscribers
                'Enlight_Controller_Action_PostDispatch_Frontend_Account' => ['__invoke', -99999],
                'Enlight_Controller_Action_PostDispatch_Frontend_Checkout' => ['__invoke', -99999],
            ],
            EnrichUserPreferenceSubscriber::getSubscribedEvents()
        );
    }

    /** @test */
    public function it_does_nothing_on_missing_user_id(): void
    {
        $this->session->get('sUserId')->willReturn(null);
        $eventArgs = $this->buildEventArgs('', $viewData = ['data' => 'view-data']);

        $this->subscriber->__invoke($eventArgs);

        $this->assertEquals($viewData, $eventArgs->getSubject()->View()->getAssign());
    }

    /** @test */
    public function it_does_nothing_on_missing_user_preference(): void
    {
        $this->session->get('sUserId')->willReturn($userId = 1234);
        $this->userPreferenceRepository->findOneBy(['userId' => $userId])->willReturn(null);

        $eventArgs = $this->buildEventArgs('', $viewData = ['data' => 'view-data']);

        $this->subscriber->__invoke($eventArgs);

        $this->assertEquals($viewData, $eventArgs->getSubject()->View()->getAssign());
    }

    /** @test */
    public function it_will_enrich_the_view_with_the_user_preference(): void
    {
        $this->session->get('sUserId')->willReturn($userId = 1234);

        $userPreference = new UserPreference();
        $userPreference->setId($id = 123123123);
        $userPreference->setUserId($userId);
        $userPreference->setStoredMethodId($storedMethodId = 'storedMethodId');
        $this->userPreferenceRepository->findOneBy(['userId' => $userId])->willReturn($userPreference);

        $eventArgs = $this->buildEventArgs('', $viewData = ['data' => 'view-data']);

        $this->subscriber->__invoke($eventArgs);

        $expected = [
            'data' => 'view-data',
            'adyenUserPreference' => [
                'id' => $id,
                'userId' => $userId,
                'storedMethodId' => $storedMethodId,
            ],
        ];
        $this->assertEquals($expected, $eventArgs->getSubject()->View()->getAssign());
    }
}
