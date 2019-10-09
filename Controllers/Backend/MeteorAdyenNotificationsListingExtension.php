<?php

use MeteorAdyen\Models\Enum\NotificationStatus;
use MeteorAdyen\Models\Notification;

class Shopware_Controllers_Backend_MeteorAdyenNotificationsListingExtension
    extends Shopware_Controllers_Backend_Application
{
    protected $model = Notification::class;
    protected $alias = 'notification';

    protected function getListQuery()
    {
        $builder = parent::getListQuery();

        $builder->leftJoin('notification.order', 'nOrder');
        $builder->addSelect(array('nOrder'));

        return $builder;
    }

    protected function getDetailQuery($id)
    {
        $builder = parent::getDetailQuery($id);

        $builder->leftJoin('notification.order', 'nOrder');
        $builder->addSelect(array('nOrder'));

        return $builder;
    }

    public function getEventCodesAction()
    {
        $eventCodes = $this->getManager()->createQueryBuilder()
            ->select('n.eventCode')
            ->distinct()
            ->from(Notification::class, 'n')
            ->getQuery()
            ->getResult();

        $this->view->assign('eventCodes', $eventCodes);
    }

    public function getNotificationStatussesAction()
    {
        $statusses = array_map(function ($status) {
            return ['status' => $status];
        }, NotificationStatus::getStatusses());
        $this->view->assign('statusses', $statusses);
    }
}
