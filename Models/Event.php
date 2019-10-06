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
}