<?php

namespace MeteorAdyen\Models;

/**
 * Class Event
 * @package MeteorAdyen\Models
 */
class Event
{
    const NOTIFICATION_RECEIVE = 'MeteorAdyen_Notification_onReceive_json';
    const NOTIFICATION_SAVE_FILTER_NOTIFICATIONS = 'MeteorAdyen_Notification_saveNotifications_notifications';
    const NOTIFICATION_ON_SAVE_NOTIFICATIONS = 'MeteorAdyen_Notification_saveNotifications_onSave';
    const NOTIFICATION_FIND_HANDLERS = 'MeteorAdyen_Notification_FindHandlers';
    const NOTIFICATION_PROCESS = 'MeteorAdyen_Notification_Process';
    const NOTIFICATION_PROCESS_AUTHORISATION = 'MeteorAdyen_Notification_Process_Authorisation';
    const NOTIFICATION_PROCESS_CANCELLATION = 'MeteorAdyen_Notification_Process_Cancellation';
    const NOTIFICATION_PROCESS_CAPTURE = 'MeteorAdyen_Notification_Process_Capture';
    const NOTIFICATION_PROCESS_CAPTURE_FAILED = 'MeteorAdyen_Notification_Process_CaptureFailed';
}