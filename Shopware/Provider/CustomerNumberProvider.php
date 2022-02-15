<?php

declare(strict_types=1);

namespace AdyenPayment\Shopware\Provider;

use Enlight_Components_Session_Namespace;
use Shopware\Components\Model\ModelManager;
use Shopware\Models\Customer\Customer;

final class CustomerNumberProvider implements CustomerNumberProviderInterface
{
    private Enlight_Components_Session_Namespace $session;
    private ModelManager $modelManager;

    public function __construct(Enlight_Components_Session_Namespace $session, ModelManager $modelManager)
    {
        $this->session = $session;
        $this->modelManager = $modelManager;
    }

    public function __invoke(): string
    {
        $userId = $this->session->get('sUserId');
        if (!$userId) {
            return '';
        }
        $customer = $this->modelManager->getRepository(Customer::class)->find($userId);

        return $customer ? (string) $customer->getNumber() : '';
    }
}
