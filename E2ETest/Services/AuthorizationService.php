<?php

namespace AdyenPayment\E2ETest\Services;

use AdyenPayment\E2ETest\Repositories\UserRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Shopware\Models\User\User;

/**
 * Class AuthorizationService
 *
 * @package AdyenPayment\E2ETest\Services
 */
class AuthorizationService
{
    /**
     * @var UserRepository
     */
    private $userRepository;

    /**
     * AuthorizationService constructor.
     */
    public function __construct()
    {
        $this->userRepository = new UserRepository();
    }

    /**
     *  Returns authorization credentials for Shopware Rest API calls
     *
     * @return string
     * @throws OptimisticLockException|ORMException
     */
    public function getAuthorizationCredentials(): string
    {
        $users = $this->userRepository->getShopwareAuthUsers();
        if (count($users) > 0) {
            $this->generateAPIKey($users[0]);

            return base64_encode($users[0]->getUsername() . ':' . $users[0]->getApiKey());
        }

        return '';
    }

    /**
     * Generates API key and saves in database for given user
     *
     * @param User $user
     * @return void
     * @throws OptimisticLockException|ORMException
     */
    private function generateAPIKey(User $user): void
    {
        if (!$user->getApiKey()) {
            $apiKey = substr(base64_encode($user->getUsername() . ':' . $user->getPassword()), 0, 40);
            $user->setApiKey($apiKey);
            $user->setPassword($user->getPassword());

            $manager = Shopware()->Models();
            $manager->persist($user);
            $manager->flush();
        }
    }
}