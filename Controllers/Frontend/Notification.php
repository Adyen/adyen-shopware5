<?php

use Adyen\AdyenException;
use Adyen\Util\HmacSignature;
use AdyenPayment\Components\Configuration;
use AdyenPayment\Components\IncomingNotificationManager;
use AdyenPayment\Exceptions\InvalidAuthenticationException;
use AdyenPayment\Exceptions\InvalidHmacException;
use AdyenPayment\Models\Event;
use Psr\Log\LoggerInterface;
use Shopware\Components\ContainerAwareEventManager;
use Shopware\Components\CSRFWhitelistAware;

//phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps, Generic.Files.LineLength.TooLong
class Shopware_Controllers_Frontend_Notification extends Shopware_Controllers_Frontend_Payment implements CSRFWhitelistAware
{
    /**
     * @var ContainerAwareEventManager
     */
    private $events;
    /**
     * @var IncomingNotificationManager
     */
    private $incomingNotificationsManager;
    /**
     * @var Configuration
     */
    private $configuration;
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @throws Exception
     */
    public function preDispatch()
    {
        $this->Front()->Plugins()->ViewRenderer()->setNoRender();
        $this->events = $this->get('events');
        $this->incomingNotificationsManager = $this->get('adyen_payment.components.incoming_notification_manager');
        $this->configuration = $this->get('adyen_payment.components.configuration');
        $this->logger = $this->get('adyen_payment.logger.notifications');
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

    /**
     * POST: /notification/adyen
     */
    public function adyenAction()
    {
        try {
            $this->checkAuthentication();
            $notifications = $this->getNotificationItems();
            $this->checkHMAC($notifications);

            $this->saveNotifications($notifications);
        } catch (InvalidAuthenticationException $exception) {
            $this->logger->critical($exception->getMessage(), [
                'trace' => $exception->getTraceAsString(),
                'previous' => $exception->getPrevious()
            ]);
            $this->View()->assign('[Invalid or missing auth]');

            return;
        } catch (InvalidHmacException $exception) {
            $this->logger->critical($exception->getMessage(), [
                'trace' => $exception->getTraceAsString(),
                'previous' => $exception->getPrevious()
            ]);
            $this->View()->assign('[wrong hmac detected]');

            return;
        } catch (\Exception $exception) {
            $this->logger->error($exception->getMessage(), [
                'trace' => $exception->getTraceAsString(),
                'previous' => $exception->getPrevious()
            ]);
        }

        // on valid credentials, always return ACCEPTED
        $this->View()->assign('[accepted]');
    }

    /**
     * Whitelist notifyAction
     */
    public function getWhitelistedCSRFActions()
    {
        return ['adyen'];
    }

    /**
     * @return array|mixed
     * @throws Enlight_Event_Exception
     */
    private function getNotificationItems()
    {
        $jsonbody = json_decode($this->Request()->getRawBody(), true);
        $notificationItems = $jsonbody['notificationItems'] ?? [];
        if (!$notificationItems) {
            return [];
        }

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
     */
    private function checkHMAC(array $notifications)
    {
        $adyenUtils = new HmacSignature();
        foreach ($notifications as $notificationItem) {
            $params = $notificationItem['NotificationRequestItem'];
            try {
                $hmacCheck = $adyenUtils->isValidNotificationHMAC($this->configuration->getNotificationHmac(), $params);
                if (!$hmacCheck) {
                    throw InvalidHmacException::withHmacKey($params["additionalData"]["hmacSignature"] ?? '');
                }
            } catch (AdyenException $exception) {
                throw InvalidHmacException::fromAdyenException($exception);
            }
        }
    }

    /**
     * @param array $notifications
     * @return Generator
     * @throws Enlight_Event_Exception
     */
    private function saveNotifications(array $notifications)
    {
        $notifications = $this->events->filter(
            Event::NOTIFICATION_SAVE_FILTER_NOTIFICATIONS,
            $notifications
        );

        return iterator_count($this->incomingNotificationsManager->save($notifications)) === 0;
    }

    /**
     * @throws InvalidAuthenticationException
     */
    private function checkAuthentication()
    {
        if (!isset($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW'])) {
            throw InvalidAuthenticationException::missingAuthentication();
        }
        $authUsername = $_SERVER['PHP_AUTH_USER'];
        $authPassword = $_SERVER['PHP_AUTH_PW'];
        if ($authUsername !== $this->configuration->getNotificationAuthUsername() ||
            $authPassword !== $this->configuration->getNotificationAuthPassword()) {
            throw InvalidAuthenticationException::invalidCredentials();
        }
    }
}
