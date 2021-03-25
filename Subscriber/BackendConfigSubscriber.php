<?php declare(strict_types=1);

namespace AdyenPayment\Subscriber;

use Adyen\AdyenException;
use Enlight\Event\SubscriberInterface;
use Enlight_Event_EventArgs;
use AdyenPayment\Components\OriginKeysService;
use AdyenPayment\Components\ShopwareVersionCheck;
use AdyenPayment\AdyenPayment;
use Psr\Log\LoggerInterface;
use Shopware\Components\CacheManager;
use Shopware_Controllers_Backend_Config;

/**
 * Class BackendConfigSubscriber
 * @package AdyenPayment\Subscriber
 */
// se-remove die(): remove OriginKeys, replace by ClientKey
class BackendConfigSubscriber implements SubscriberInterface
{
    /** @var OriginKeysService */
    private $originKeysService;
    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var ShopwareVersionCheck
     */
    private $shopwareVersionCheck;

    /**
     * BackendConfigSubscriber constructor.
     * @param OriginKeysService $originKeysService
     */
    public function __construct(
        OriginKeysService $originKeysService,
        LoggerInterface $logger,
        ShopwareVersionCheck $shopwareVersionCheck
    ) {
        $this->originKeysService = $originKeysService;
        $this->logger = $logger;
        $this->shopwareVersionCheck = $shopwareVersionCheck;
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

        if ($subject->Request()->getActionName() === 'saveForm'
            && $subject->Request()->getParam('name') === AdyenPayment::NAME
        ) {
            try {
                $this->originKeysService->generateAndSave();
            } catch (AdyenException $ex) {
                $this->logger->error('AdyenException on Backend Config', [
                    'message' => $ex->getMessage(),
                    'file' => $ex->getFile(),
                    'line' => $ex->getLine()
                ]);
            }

            if ($this->shopwareVersionCheck->isHigherThanShopwareVersion('v5.5.6')) {
                $subject->get('shopware.cache_manager')->clearByTags([CacheManager::CACHE_TAG_CONFIG]);
            }
        }
    }
}
