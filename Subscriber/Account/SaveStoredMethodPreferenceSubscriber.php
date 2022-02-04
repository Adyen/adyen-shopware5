<?php

declare(strict_types=1);

namespace AdyenPayment\Subscriber\Account;

use AdyenPayment\Components\Adyen\PaymentMethod\StoredPaymentMeanProviderInterface;
use AdyenPayment\Components\Manager\UserPreferenceManagerInterface;
use AdyenPayment\Models\UserPreference;
use Doctrine\ORM\EntityRepository;
use Enlight\Event\SubscriberInterface;
use Enlight_Components_Session_Namespace;

final class SaveStoredMethodPreferenceSubscriber implements SubscriberInterface
{
    private Enlight_Components_Session_Namespace $session;
    private UserPreferenceManagerInterface $userPreferenceManager;
    private EntityRepository $userPreferenceRepository;
    private StoredPaymentMeanProviderInterface $storedPaymentMeanProvider;

    public function __construct(
        Enlight_Components_Session_Namespace $session,
        UserPreferenceManagerInterface $userPreferenceManager,
        EntityRepository $userPreferenceRepository,
        StoredPaymentMeanProviderInterface $storedPaymentMeanProvider
    ) {
        $this->session = $session;
        $this->userPreferenceManager = $userPreferenceManager;
        $this->userPreferenceRepository = $userPreferenceRepository;
        $this->storedPaymentMeanProvider = $storedPaymentMeanProvider;
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

        $storedMethod = $this->storedPaymentMeanProvider->fromRequest($request);
        $storedMethodId = null !== $storedMethod ? $storedMethod->getValue('stored_method_id') : null;

        $userPreference = $this->userPreferenceRepository->findOneBy(['userId' => $userId]);
        if (null === $userPreference) {
            $userPreference = new UserPreference();
            $userPreference->setUserId($userId);
        }

        $userPreference = $userPreference->setStoredMethodId($storedMethodId);
        $this->userPreferenceManager->save($userPreference);
    }
}
