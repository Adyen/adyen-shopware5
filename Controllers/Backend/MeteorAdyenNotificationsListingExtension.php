<?php

use MeteorAdyen\Models\Notification;

class Shopware_Controllers_Backend_MeteorAdyenNotificationsListingExtension extends Shopware_Controllers_Backend_Application
{
    protected $model = Notification::class;
    protected $alias = 'notification';
}
