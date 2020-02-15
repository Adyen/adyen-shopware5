<?php

declare(strict_types=1);

namespace MeteorAdyen\Subscriber;

use Adyen\AdyenException;
use Enlight\Event\SubscriberInterface;
use Enlight_Event_EventArgs;
use MeteorAdyen\Components\OriginKeysService;
use MeteorAdyen\MeteorAdyen;
use Psr\Log\LoggerInterface;
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
     * @var LoggerInterface
     */
    private $logger;

    /**
     * BackendConfigSubscriber constructor.
     * @param OriginKeysService $originKeysService
     */
    public function __construct(
        OriginKeysService $originKeysService,
        LoggerInterface $logger
    ) {
        $this->originKeysService = $originKeysService;
        $this->logger = $logger;
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

        if ($subject->Request()->getActionName() == 'saveForm' && $subject->Request()->getParam('name') === MeteorAdyen::NAME) {
            try {
                $this->generateOriginKeys($subject);
            } catch (AdyenException $e) {
                $this->logger->error($e);
            }
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
