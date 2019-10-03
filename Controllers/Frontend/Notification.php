<?php

use MeteorAdyen\Components\Configuration;
use Shopware\Components\CSRFWhitelistAware;

class Shopware_Controllers_Frontend_Notification
    extends Shopware_Controllers_Frontend_Payment
    implements CSRFWhitelistAware
{
    /**
     * /notification
     */
    public function indexAction()
    {
        if (!$this->checkAuthentication()) {
            return;
        }

        $notifications = $this->getNotificationItems();

        if (!$this->checkHMAC($notifications)) {
            $this->View()->assign(['notificationResponse' => "[wrong hmac]"]);
            return;
        }

        if (!$this->saveNotifications($notifications)) {
            $this->View()->assign(['notificationResponse' => "[notification save error]"]);
            return;
        }

        $this->View()->assign(['notificationResponse' => "[accepted]"]);
    }

    /**
     * @return array
     */
    private function getNotificationItems() {
        $jsonbody = json_decode($this->Request()->getContent(), true);
        $notificationItems = $jsonbody['notificationItems'];

        return $notificationItems;
    }

    /**
     * @param $notifications
     * @return bool
     * @throws \Adyen\AdyenException
     */
    private function checkHMAC($notifications)
    {
        /** @var Configuration $configuration */
        $configuration = $this->get('meteor_adyen.components.configuration');
        $adyenUtils = new \Adyen\Util\HmacSignature();

        foreach ($notifications as $notificationItem) {
            $params = $notificationItem['NotificationRequestItem'];
            $hmacCheck = $adyenUtils->isValidNotificationHMAC($configuration->getNotificationHmac(), $params);
            if (!$hmacCheck) {
                return false;
            }
        }
        return true;
    }

    private function saveNotifications(array $notifications)
    {
        /** @var Enlight_Event_EventManager $eventManager */
        $eventManager = $this->get('events');
        $notifications = $eventManager->filter(
            'MeteorAdyen_Notification_saveNotifications',
            $notifications
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

        $this->Response()->headers->set('content-type', 'application/json', true);
        $this->Response()->setContent($data);
    }

    private function checkAuthentication()
    {
        /** @var Configuration $configuration */
        $configuration = $this->get('meteor_adyen.components.configuration');

        $authUsername = $_SERVER['PHP_AUTH_USER'];
        $authPassword = $_SERVER['PHP_AUTH_PW'];

        if ($authUsername !== $configuration->getNotificationAuthUsername() ||
            $authPassword !== $configuration->getNotificationAuthPassword()) {
            $this->View()->assign(['success' => false, 'message' => 'Invalid or missing auth']);

            return false;
        }

        return true;
    }
}
