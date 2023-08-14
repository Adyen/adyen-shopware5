<?php

use Adyen\Core\Infrastructure\AutoTest\AutoTestService;
use Adyen\Core\Infrastructure\Logger\Logger;
use Adyen\Core\Infrastructure\ServiceRegister;
use Adyen\Core\Infrastructure\TaskExecution\Interfaces\AsyncProcessService;
use AdyenPayment\Controllers\Common\AjaxResponseSetter;
use Shopware\Components\CSRFWhitelistAware;

/**
 * Class Shopware_Controllers_Frontend_AdyenAsyncProcess
 */
class Shopware_Controllers_Frontend_AdyenAsyncProcess extends Enlight_Controller_Action implements CSRFWhitelistAware
{
    /**
     * Returns a list with actions which should not be validated for CSRF protection
     *
     * @return string[]
     */
    public function getWhitelistedCSRFActions()
    {
        return ['run'];
    }

    /**
     * Starts async process.
     */
    public function runAction()
    {
        $guid = $this->Request()->getParam('guid', '');
        $autoTest = $this->Request()->getParam('auto-test', false);

        if ($autoTest) {
            $autoTestService = new AutoTestService();
            $autoTestService->setAutoTestMode();
            Logger::logInfo('Received auto-test async process request', 'Integration');
        } else {
            Logger::logDebug("Received async process request with guid [{$guid}].", 'Integration');
        }

        if ($guid !== 'auto-configure') {
            /** @var AsyncProcessService $asyncProcessService */
            $asyncProcessService = ServiceRegister::getService(AsyncProcessService::CLASS_NAME);
            $asyncProcessService->runProcess($guid);
        }

        $this->Front()->Plugins()->ViewRenderer()->setNoRender();
        $this->Response()->setHeader('Content-Type', 'application/json');

        $this->Response()->setBody(
            json_encode(['response', ['success' => true]])
        );

    }
}
