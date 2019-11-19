<?php

use Adyen\AdyenException;
use Adyen\Util\HmacSignature;
use MeteorAdyen\Components\Configuration;
use MeteorAdyen\Models\Event;
use Shopware\Components\ContainerAwareEventManager;
use Shopware\Components\CSRFWhitelistAware;

class Shopware_Controllers_Frontend_Notification extends Shopware_Controllers_Frontend_Payment implements CSRFWhitelistAware
{
    /**
     * @var ContainerAwareEventManager
     */
    private $events;

    /**
     * POST: /notification
     * @throws Enlight_Event_Exception
     * @throws AdyenException
     */
    public function indexAction()
    {
        if (!$this->checkAuthentication()) {
            $this->View()->assign(['success' => false, 'message' => 'Invalid or missing auth']);
            return;
        }

        $notifications = $this->getNotificationItems();

        if (!$this->checkHMAC($notifications)) {
            $this->View()->assign(['notificationResponse' => "[wrong hmac detected]"]);
            return;
        }

        if (!$this->saveNotifications($notifications)) {
            $this->View()->assign(['notificationResponse' => "[notification save error]"]);
            return;
        }

        $this->View()->assign(['notificationResponse' => "[accepted]"]);
    }

    /**
     * @return mixed
     * @throws Enlight_Event_Exception
     */
    private function getNotificationItems()
    {
        $jsonbody = json_decode($this->Request()->getRawBody(), true);
        $notificationItems = $jsonbody['notificationItems'];

        $this->events->notify(
            Event::NOTIFICATION_RECEIVE,
            [
                'items' => $notificationItems
            ]
        );

        return $notificationItems;
    }

    /**
     * @param $notifications
     * @return bool
     * @throws AdyenException
     */
    private function checkHMAC($notifications)
    {
        /** @var Configuration $configuration */
        $configuration = $this->get('meteor_adyen.components.configuration');
        $adyenUtils = new HmacSignature();

        foreach ($notifications as $notificationItem) {
            $params = $notificationItem['NotificationRequestItem'];
            $hmacCheck = $adyenUtils->isValidNotificationHMAC($configuration->getNotificationHmac(), $params);
            if (!$hmacCheck) {
                $this->get('meteor_adyen.logger.notifications')->notice('Invalid HMAC detected');
                return false;
            }
        }
        return true;
    }

    /**
     * @param array $notifications
     * @return Enlight_Event_EventArgs|null
     * @throws Enlight_Event_Exception
     */
    private function saveNotifications(array $notifications)
    {
        $notifications = $this->events->filter(
            Event::NOTIFICATION_SAVE_FILTER_NOTIFICATIONS,
            $notifications
        );
        return $this->events->notify(
            Event::NOTIFICATION_ON_SAVE_NOTIFICATIONS,
            [
                'params' => $notifications
            ]
        );
    }

    /**
     * Whitelist notifyAction
     */
    public function getWhitelistedCSRFActions()
    {
        return ['index'];
    }

    /**
     * @throws Exception
     */
    public function preDispatch()
    {
        $this->Front()->Plugins()->ViewRenderer()->setNoRender();

        /** @var Enlight_Event_EventManager $eventManager */
        $this->events = $this->get('events');
    }

    public function postDispatch()
    {
        $data = $this->View()->getAssign();
        $pretty = $this->Request()->getParam('pretty', false);

        array_walk_recursive($data, static function (&$value) {
            // Convert DateTime instances to ISO-8601 Strings
            if ($value instanceof DateTime) {
                $value = $value->format(DateTime::ISO8601);
            }
        });

        $data = Zend_Json::encode($data);
        if ($pretty) {
            $data = Zend_Json::prettyPrint($data);
        }

        $this->Response()->setHeader('content-type', 'application/json', true);
        $this->Response()->setBody($data);
    }

    private function checkAuthentication()
    {
        /** @var Configuration $configuration */
        $configuration = $this->get('meteor_adyen.components.configuration');

        if (!isset($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW'])) {
            return false;
        }

        $authUsername = $_SERVER['PHP_AUTH_USER'];
        $authPassword = $_SERVER['PHP_AUTH_PW'];

        if ($authUsername !== $configuration->getNotificationAuthUsername() ||
            $authPassword !== $configuration->getNotificationAuthPassword()) {
            return false;
        }

        return true;
    }
}
