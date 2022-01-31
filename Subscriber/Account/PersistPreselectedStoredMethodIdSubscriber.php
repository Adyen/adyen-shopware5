<?php

declare(strict_types=1);

namespace AdyenPayment\Subscriber\Account;

use AdyenPayment\Components\Manager\UserPreferenceManagerInterface;
use Enlight\Event\SubscriberInterface;
use Enlight_Components_Session_Namespace;
use Enlight_Controller_Request_Request;

final class PersistPreselectedStoredMethodIdSubscriber implements SubscriberInterface
{
    private Enlight_Components_Session_Namespace $session;
    private UserPreferenceManagerInterface $userPreferenceManager;

    public function __construct(
        Enlight_Components_Session_Namespace $session,
        UserPreferenceManagerInterface $userPreferenceManager
    ) {
        $this->session = $session;
        $this->userPreferenceManager = $userPreferenceManager;
    }

    public static function getSubscribedEvents(): array
    {
        return ['Enlight_Controller_Action_PostDispatch_Frontend_Account' => '__invoke'];
    }

    public function __invoke(\Enlight_Controller_ActionEventArgs $args): void
    {
        $userId = $this->session->get('sUserId');
        if (!$userId) {
            return;
        }

        $request = $args->getRequest();

        $isSavePayment = 'savePayment' === $request->getActionName() && $request->isPost();
        if (!$isSavePayment) {
            return;
        }

        $storedMethodId = $this->storedMethodIdFromRequest($request);
        $this->userPreferenceManager->upsertStoredMethodIdByUserId($userId, $storedMethodId);
    }

    private function storedMethodIdFromRequest(Enlight_Controller_Request_Request $request): ?string
    {
        $registerPayment = $request->getParam('register', [])['payment'] ?? null;
        if (null === $registerPayment) {
            return null;
        }

        $splitMethod = explode('_', $registerPayment);

        return 1 < count($splitMethod) ? $splitMethod[1] : null;
    }
}
