<?php

use Adyen\Core\BusinessLogic\AdminAPI\AdminAPI;
use Adyen\Core\Infrastructure\ServiceRegister;
use Adyen\Core\Infrastructure\TaskExecution\Interfaces\TaskRunnerWakeup;
use AdyenPayment\Controllers\Common\AjaxResponseSetter;

/**
 * Class Shopware_Controllers_Backend_AdyenState
 */
class Shopware_Controllers_Backend_AdyenState extends Enlight_Controller_Action
{
    use AjaxResponseSetter;

    /**
     * @return void
     */
    public function indexAction(): void
    {
        $storeId = $this->Request()->get('storeId');
        $result = AdminAPI::get()->integration($storeId)->getState();
        $this->getTaskRunnerWakeUp()->wakeup();

        $this->returnAPIResponse($result);
    }

    /**
     * @return TaskRunnerWakeup
     */
    private function getTaskRunnerWakeUp(): TaskRunnerWakeup
    {
        return ServiceRegister::getService(TaskRunnerWakeup::CLASS_NAME);
    }
}
