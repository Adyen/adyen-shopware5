<?php

namespace AdyenPayment\Models;

/**
 * Class Event
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
}
