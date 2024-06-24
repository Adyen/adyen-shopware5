<?php

declare(strict_types=1);

namespace AdyenPayment\Subscriber\Account;

use AdyenPayment\Models\UserPreference;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Enlight\Event\SubscriberInterface;
use Enlight_Components_Session_Namespace;

final class SaveStoredMethodPreference implements SubscriberInterface
{
    /** @var Enlight_Components_Session_Namespace */
    private $session;
    /** @var EntityRepository */
    private $userPreferenceRepository;
    /**
     * @var EntityManager
     */
    private $modelManager;

    public function __construct(
        Enlight_Components_Session_Namespace $session,
        EntityManager $modelManager
    ) {
        $this->session = $session;
        $this->userPreferenceRepository = Shopware()->Models()->getRepository(UserPreference::class);
        $this->modelManager = $modelManager;
    }

    public static function getSubscribedEvents(): array
    {
        return ['Enlight_Controller_Action_PostDispatch_Frontend_Account' => '__invoke'];
    }

    public function __invoke(\Enlight_Controller_ActionEventArgs $args): void
    {
        $userId = $this->session->get('sUserId');
        if (null === $userId) {
            return;
        }

        $request = $args->getRequest();

        $isSavePayment = 'savePayment' === $request->getActionName() && $request->isPost();
        if (!$isSavePayment) {
            return;
        }

        $selectedPaymentMeanId = $request->getParam('register', [])['payment'] ?? null;
        if (null === $selectedPaymentMeanId) {
            return;
        }

        // Stored payment mean id is in format umbrellaPaymentId_storedPaymentMethodId
        $storedMethodIdParts = explode('_', $selectedPaymentMeanId);
        $storedMethodId = 2 === count($storedMethodIdParts) ? $storedMethodIdParts[1] : null;

        $userPreference = $this->userPreferenceRepository->findOneBy(['userId' => $userId]);
        if (null === $userPreference) {
            $userPreference = new UserPreference();
            $userPreference->setUserId($userId);
        }

        $userPreference = $userPreference->setStoredMethodId($storedMethodId);
        $this->modelManager->persist($userPreference);
        $this->modelManager->flush($userPreference);
    }
}
