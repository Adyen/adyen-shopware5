<?php

declare(strict_types=1);

namespace AdyenPayment\Tests\Unit\Subscriber;

use AdyenPayment\Tests\Unit\Mock\ControllerActionMock;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;
use Webmozart\Assert\Assert;

abstract class SubscriberTestCase extends TestCase
{
    protected function buildEventArgs(
        string $actionName,
        array $viewData,
        int $status = Response::HTTP_OK
    ): \Enlight_Controller_ActionEventArgs {
        return new \Enlight_Controller_ActionEventArgs([
            'subject' => $this->buildSubject($viewData),
            'request' => (new \Enlight_Controller_Request_RequestTestCase())->setActionName($actionName),
            'response' => new \Enlight_Controller_Response_ResponseTestCase('', $status),
        ]);
    }

    protected function buildSubject(array $viewData): \Enlight_Controller_Action
    {
        Assert::allString(array_keys($viewData));

        $subject = new ControllerActionMock();
        $subject->setView(new \Enlight_View_Default(new \Enlight_Template_Manager()));
        $subject->View()->assign($viewData);

        return $subject;
    }
}
