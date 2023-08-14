<?php

use Adyen\Core\BusinessLogic\AdminAPI\AdminAPI;
use AdyenPayment\Controllers\Common\AjaxResponseSetter;
use AdyenPayment\Repositories\Wrapper\OrderRepository;

/**
 * Class Shopware_Controllers_Backend_AdyenNotifications
 */
class Shopware_Controllers_Backend_AdyenNotifications extends Enlight_Controller_Action
{
    use AjaxResponseSetter {
        AjaxResponseSetter::preDispatch as protected ajaxResponseSetterPreDispatch;
    }

    /**
     * @var OrderRepository
     */
    private $orderRepository;

    /**
     * @return void
     * @throws Exception
     */
    public function preDispatch(): void
    {
        $this->ajaxResponseSetterPreDispatch();
        $this->orderRepository = $this->get(OrderRepository::class);
    }

    /**
     * @return void
     *
     * @throws Exception
     */
    public function getNotificationsAction(): void
    {
        $storeId = $this->Request()->get('storeId');
        $page = $this->Request()->get('page', 1);
        $limit = $this->Request()->get('limit', 10);
        $result = AdminAPI::get()->shopNotifications($storeId)->getNotifications($page, $limit);

        if (!$result->isSuccessful()) {
            $this->returnAPIResponse($result);

             return;
        }

        $jsonResponse = $result->toArray();
        $map = $this->mapOrderNumbers($this->getMerchantReferences($jsonResponse['notifications']));

        foreach ($jsonResponse['notifications'] as $key => $item) {
            $jsonResponse['notifications'][$key]['orderId'] = $map[$item['orderId']];
        }

        $this->Response()->setHeader('Content-Type', 'application/json');
        $this->Response()->setBody(json_encode($jsonResponse));
    }

    /**
     * @param array $notifications
     *
     * @return array
     */
    private function getMerchantReferences(array $notifications): array
    {
        return array_unique(
            array_map(static function (array $notifications) {
                return $notifications['orderId'];
            }, $notifications)
        );
    }

    /**
     * @param string[] $references
     *
     * @return array
     */
    private function mapOrderNumbers(array $references): array
    {
        if (empty($references)) {
            return [];
        }

        $ordersMap = $this->orderRepository->getOrderNumbersFor($references);

        $orderNumbers = [];
        foreach ($references as $reference) {
            $orderNumbers[$reference] = array_key_exists($reference, $ordersMap) ? $ordersMap[$reference] : '';
        }

        return $orderNumbers;
    }
}
