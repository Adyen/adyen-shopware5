<?php

use AdyenPayment\Models\Enum\NotificationStatus;
use AdyenPayment\Models\Notification;

//phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps, Generic.Files.LineLength.TooLong
class Shopware_Controllers_Backend_AdyenPaymentNotificationsListingExtension extends Shopware_Controllers_Backend_Application
{
    protected $model = Notification::class;
    protected $alias = 'notification';

    /**
     * Joins order to notification in list query
     *
     * @return \Shopware\Components\Model\QueryBuilder
     */
    protected function getListQuery()
    {
        $builder = parent::getListQuery();

        $builder->leftJoin('notification.order', 'nOrder')
            ->addSelect(['nOrder']);

        return $builder;
    }

    /**
     * Joins order to notification in detail query
     *
     * @param int $id
     * @return \Shopware\Components\Model\QueryBuilder
     */
    protected function getDetailQuery($id)
    {
        $builder = parent::getDetailQuery($id);

        $builder->leftJoin('notification.order', 'nOrder')
            ->addSelect(['nOrder']);

        return $builder;
    }

    /**
     * Returns distinct Event Codes in json array
     */
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

    /**
     * Returns all NotificationStatusses in json array
     */
    public function getNotificationStatussesAction()
    {
        $statusses = array_map(function ($status) {
            return ['status' => $status];
        }, NotificationStatus::getStatusses());
        $this->view->assign('statusses', $statusses);
    }
}
