<?php

declare(strict_types=1);

namespace AdyenPayment\Tests\Unit\Components\Manager;

use AdyenPayment\Components\Manager\UserPreferenceManager;
use AdyenPayment\Components\Manager\UserPreferenceManagerInterface;
use AdyenPayment\Models\UserPreference;
use Doctrine\ORM\EntityManager;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;

final class UserPreferenceManagerTest extends TestCase
{
    use ProphecyTrait;
    private UserPreferenceManager $userPreferenceManager;

    /**
     * @var EntityManager|ObjectProphecy
     */
    private $modelManager;

    protected function setUp(): void
    {
        $this->modelManager = $this->prophesize(EntityManager::class);

        $this->userPreferenceManager = new UserPreferenceManager($this->modelManager->reveal());
    }

    /** @test */
    public function it_is_an_user_preference_manager(): void
    {
        $this->assertInstanceOf(UserPreferenceManagerInterface::class, $this->userPreferenceManager);
    }

    /** @test */
    public function it_can_save_a_record(): void
    {
        $userPreference = new UserPreference();
        $userPreference->setUserId(1234);
        $userPreference->setStoredMethodId('expected-method-id');

        $this->modelManager->persist($userPreference)->shouldBeCalled();
        $this->modelManager->flush($userPreference)->shouldBeCalled();

        $this->userPreferenceManager->save($userPreference);
    }
}
