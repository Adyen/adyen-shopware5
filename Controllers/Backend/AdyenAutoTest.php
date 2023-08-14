<?php

use Adyen\Core\BusinessLogic\AdminAPI\AdminAPI;
use Adyen\Core\Infrastructure\Exceptions\StorageNotAccessibleException;
use Adyen\Core\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException;
use Adyen\Core\Infrastructure\ORM\Exceptions\RepositoryClassException;
use Adyen\Core\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException;
use Adyen\Core\Infrastructure\TaskExecution\Exceptions\QueueStorageUnavailableException;
use AdyenPayment\Controllers\Common\AjaxResponseSetter;

/**
 * Class Shopware_Controllers_Backend_AdyenAutoTest
 */
class Shopware_Controllers_Backend_AdyenAutoTest extends Enlight_Controller_Action
{
    use AjaxResponseSetter;

    /**
     * @return void
     *
     * @throws StorageNotAccessibleException
     * @throws QueueStorageUnavailableException
     */
    public function startAutoTestAction(): void
    {
        $result = AdminAPI::get()->autoTest()->startAutoTest();

        $this->returnAPIResponse($result);
    }

    /**
     * @return void
     *
     * @throws QueryFilterInvalidParamException
     * @throws RepositoryClassException
     * @throws RepositoryNotRegisteredException
     */
    public function autoTestStatusAction(): void
    {
        $queueItemId = $this->Request()->get('queueItemId');
        $result = AdminAPI::get()->autoTest()->autoTestStatus($queueItemId ?? 0);

        $this->returnAPIResponse($result);
    }

    /**
     * @return void
     *
     * @throws RepositoryNotRegisteredException
     * @throws Exception
     */
    public function getReportAction(): void
    {
        $result = AdminAPI::get()->autoTest()->autoTestReport();

        $data = json_encode($result->toArray(), JSON_PRETTY_PRINT);
        $response = $this->Response();
        $response->headers->set('content-description', 'File Transfer');
        $response->headers->set('content-type', 'application/octet-stream');
        $response->headers->set('content-disposition', 'attachment; filename=auto-test-logs.json');
        $response->headers->set('cache-control', 'public', true);
        $response->headers->set('content-length', (string)strlen($data));
        $response->sendHeaders();

        $this->Front()->Plugins()->ViewRenderer()->setNoRender();
        $out = fopen('php://output', 'wb');

        fwrite($out, $data);
        fclose($out);
    }
}
