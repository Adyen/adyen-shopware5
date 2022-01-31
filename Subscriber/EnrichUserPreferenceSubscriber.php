<?php

declare(strict_types=1);

namespace AdyenPayment\Subscriber;

use AdyenPayment\Models\UserPreference;
use Doctrine\ORM\EntityManager;
use Enlight\Event\SubscriberInterface;
use Enlight_Components_Session_Namespace;

final class EnrichUserPreferenceSubscriber implements SubscriberInterface
{
    private Enlight_Components_Session_Namespace $session;
    private EntityManager $modelsManager;

    public function __construct(
        Enlight_Components_Session_Namespace $session,
        EntityManager $modelsManager
    ) {
        $this->session = $session;
        $this->modelsManager = $modelsManager;
    }

    public static function getSubscribedEvents(): array
    {
        return ['Enlight_Controller_Action_PostDispatch_Frontend' => '__invoke'];
    }

    public function __invoke(\Enlight_Controller_ActionEventArgs $args): void
    {
        $userId = $this->session->get('sUserId');
        if (!$userId) {
            return;
        }

        $userPreference = $this->modelsManager->getRepository(UserPreference::class)->findOneBy(['userId' => $userId]);
        $args->getSubject()->View()->assign('adyenUserPreference', $userPreference->jsonSerialize());
    }
}
