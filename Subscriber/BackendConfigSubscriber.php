<?php

declare(strict_types=1);

namespace MeteorAdyen\Subscriber;

use Enlight\Event\SubscriberInterface;
use Enlight_Event_EventArgs;
use MeteorAdyen\Components\OriginKeysService;
use Shopware_Controllers_Backend_Config;

/**
 * Class BackendConfigSubscriber
 * @package MeteorAdyen\Subscriber
 */
class BackendConfigSubscriber implements SubscriberInterface
{
    /** @var OriginKeysService */
    private $originKeysService;

    /**
     * BackendConfigSubscriber constructor.
     * @param OriginKeysService $originKeysService
     */
    public function __construct(OriginKeysService $originKeysService)
    {
        $this->originKeysService = $originKeysService;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            'Enlight_Controller_Action_PostDispatch_Backend_Config' => 'onBackendConfig'
        ];
    }

    /**
     * @param Enlight_Event_EventArgs $args
     * @throws \Adyen\AdyenException
     */
    public function onBackendConfig(\Enlight_Event_EventArgs $args)
    {
        /** @var Shopware_Controllers_Backend_Config $subject */
        $subject = $args->getSubject();

        if ($subject->Request()->getActionName() == 'saveForm') {
            $this->generateOriginKeys($subject);
        }
    }

    /**
     * @param Shopware_Controllers_Backend_Config $subject
     * @throws \Adyen\AdyenException
     */
    private function generateOriginKeys(Shopware_Controllers_Backend_Config $subject)
    {
        $this->originKeysService->generateAndSave();
    }
}
