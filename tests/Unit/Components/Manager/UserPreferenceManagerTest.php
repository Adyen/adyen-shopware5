<?php

declare(strict_types=1);

namespace AdyenPayment\Tests\Unit\Components\Manager;

use AdyenPayment\Components\Manager\UserPreferenceManager;
use AdyenPayment\Components\Manager\UserPreferenceManagerInterface;
use AdyenPayment\Models\UserPreference;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
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
    public function it_can_insert_a_new_record(): void
    {
        $repositoryMock = $this->prophesize(EntityRepository::class);
        $repositoryMock->findOneBy(['userId' => $userId = 1234])->willReturn(null);

        $userPreference = new UserPreference();
        $userPreference->setUserId($userId);
        $userPreference->setStoredMethodId($storedMethodId = 'expected-method-id');

        $this->modelManager->getRepository(UserPreference::class)->willReturn($repositoryMock);

        $this->modelManager->persist($userPreference)->shouldBeCalled();
        $this->modelManager->flush($userPreference)->shouldBeCalled();

        $this->userPreferenceManager->upsertStoredMethodIdByUserId($userId, $storedMethodId);
    }

    /** @test */
    public function it_can_update_a_record(): void
    {
        $existentUserPreference = new UserPreference();
        $existentUserPreference->setUserId($userId = 1234);
        $existentUserPreference->setStoredMethodId($oldStoredMethodId = 'expected-method-id');

        $userPreference = new UserPreference();
        $userPreference->setUserId($userId);
        $userPreference->setStoredMethodId($storedMethodId = 'expected-method-id');

        $repositoryMock = $this->prophesize(EntityRepository::class);
        $repositoryMock->findOneBy(['userId' => $userId])->willReturn($existentUserPreference);

        $this->modelManager->getRepository(UserPreference::class)->willReturn($repositoryMock);
        $this->modelManager->persist($userPreference)->shouldBeCalled();
        $this->modelManager->flush($userPreference)->shouldBeCalled();

        $this->userPreferenceManager->upsertStoredMethodIdByUserId($userId, $storedMethodId);
    }
}
