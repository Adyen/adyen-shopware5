<?php

namespace AdyenPayment\Models;

/**
 * Class Event
 *
 * @package AdyenPayment\Models
 */
class Event
{
    const NOTIFICATION_RECEIVE = 'Adyen_Notification_onReceive_json';
    const NOTIFICATION_SAVE_FILTER_NOTIFICATIONS = 'Adyen_Notification_saveNotifications_notifications';
    const NOTIFICATION_FIND_HANDLERS = 'Adyen_Notification_FindHandlers';
    const NOTIFICATION_PROCESS = 'Adyen_Notification_Process';
    const NOTIFICATION_NO_ORDER_FOUND = 'Adyen_Notification_No_Order_Found';
    const NOTIFICATION_PROCESS_AUTHORISATION = 'Adyen_Notification_Process_Authorisation';
    const NOTIFICATION_PROCESS_CANCELLATION = 'Adyen_Notification_Process_Cancellation';
    const NOTIFICATION_PROCESS_CAPTURE = 'Adyen_Notification_Process_Capture';
    const NOTIFICATION_PROCESS_CAPTURE_FAILED = 'Adyen_Notification_Process_CaptureFailed';
    const NOTIFICATION_PROCESS_OFFER_CLOSED = 'Adyen_Notification_Process_OfferClosed';
    const NOTIFICATION_PROCESS_REFUND = 'Adyen_Notification_Process_Refund';
    const NOTIFICATION_PROCESS_REFUND_FAILED = 'Adyen_Notification_Process_RefundFailed';
    const NOTIFICATION_PROCESS_REFUNDED_REVERSED = 'Adyen_Notification_Process_RefundedReversed';
    const NOTIFICATION_PROCESS_CHARGEBACK = 'Adyen_Notification_Process_Chargeback';
    const NOTIFICATION_PROCESS_CHARGEBACK_REVERSED = 'Adyen_Notification_Process_ChargebackReversed';

    const ORDER_STATUS_CHANGED = 'Adyen_Order_Status_Changed';
    const ORDER_PAYMENT_STATUS_CHANGED= 'Adyen_Order_Payment_Status_Changed';

    const BASKET_RESTORE_FROM_ORDER = 'Adyen_Basket_RestoreFromOrder';
    const BASKET_BEFORE_PROCESS_ORDER_DETAIL = 'Adyen_Basket_Before_ProcessOrderDetail';
    const BASKET_STOPPED_PROCESS_ORDER_DETAIL = 'Adyen_Basket_Stopped_ProcessOrderDetail';
    const BASKET_AFTER_PROCESS_ORDER_DETAIL = 'Adyen_Basket_After_ProcessOrderDetail';

    private static $CRON_PROCESS_NOTIFICATIONS = 'Shopware_CronJob_AdyenPaymentProcessNotifications';
    private static $CRON_IMPORT_PAYMENT_METHODS = 'AdyenPayment_CronJob_ImportPaymentMethods';

    /**
     * @var string
     */
    private $name;

    private function __construct(string $name)
    {
        if (!in_array($name, $this->availableEventNames())) {
            throw new \InvalidArgumentException('Invalid Event name: "'.$name.'"');
        }

        $this->name = $name;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function equals(Event $event): bool
    {
        return $event->getName() === $this->name;
    }

    public static function load(string $name): self
    {
        return new self($name);
    }

    public static function cronImportPaymentMethods(): self
    {
        return new self(self::$CRON_IMPORT_PAYMENT_METHODS);
    }

    public static function cronProcessNotifications(): self
    {
        return new self(self::$CRON_PROCESS_NOTIFICATIONS);
    }

    /**
     * @return string[]
     */
    private function availableEventNames(): array
    {
        return [
            self::$CRON_PROCESS_NOTIFICATIONS,
            self::$CRON_IMPORT_PAYMENT_METHODS,

            self::NOTIFICATION_RECEIVE,
            self::NOTIFICATION_SAVE_FILTER_NOTIFICATIONS,
            self::NOTIFICATION_FIND_HANDLERS,
            self::NOTIFICATION_PROCESS,
            self::NOTIFICATION_NO_ORDER_FOUND,
            self::NOTIFICATION_PROCESS_AUTHORISATION,
            self::NOTIFICATION_PROCESS_CANCELLATION,
            self::NOTIFICATION_PROCESS_CAPTURE,
            self::NOTIFICATION_PROCESS_CAPTURE_FAILED,
            self::NOTIFICATION_PROCESS_OFFER_CLOSED,
            self::NOTIFICATION_PROCESS_REFUND,
            self::NOTIFICATION_PROCESS_REFUND_FAILED,
            self::NOTIFICATION_PROCESS_REFUNDED_REVERSED,
            self::NOTIFICATION_PROCESS_CHARGEBACK,
            self::NOTIFICATION_PROCESS_CHARGEBACK_REVERSED,
            self::ORDER_STATUS_CHANGED,
            self::ORDER_PAYMENT_STATUS_CHANGED,
            self::BASKET_RESTORE_FROM_ORDER,
            self::BASKET_BEFORE_PROCESS_ORDER_DETAIL,
            self::BASKET_STOPPED_PROCESS_ORDER_DETAIL,
            self::BASKET_AFTER_PROCESS_ORDER_DETAIL,
        ];
    }
}
