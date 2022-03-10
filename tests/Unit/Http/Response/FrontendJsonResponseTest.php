<?php

declare(strict_types=1);

namespace AdyenPayment\Tests\Unit\Http\Response;

use AdyenPayment\Http\Response\ApiJsonResponse;
use AdyenPayment\Http\Response\FrontendJsonResponse;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

class FrontendJsonResponseTest extends TestCase
{
    private ApiJsonResponse $apiJsonResponse;

    protected function setUp(): void
    {
        $this->apiJsonResponse = new FrontendJsonResponse();
    }

    /** @test */
    public function it_is_an_api_json_response(): void
    {
        self::assertInstanceOf(ApiJsonResponse::class, $this->apiJsonResponse);
    }

    /** @test */
    public function it_can_send_a_json_response(): void
    {
        $frontController = $this->prophesize(\Enlight_Controller_Front::class);
        $httpResponse = $this->prophesize(\Enlight_Controller_Response_ResponseHttp::class);
        $response = $this->prophesize(JsonResponse::class);
        $plugins = $this->prophesize(\Enlight_Plugin_Namespace_Loader::class);
        $viewRenderer = $this->prophesize(\Enlight_Controller_Plugins_ViewRenderer_Bootstrap::class);

        $response->headers = new ResponseHeaderBag(['Content-Type' => 'json']);
        $response->getStatusCode()->willReturn($statusCode = 200);
        $response->getContent()->willReturn($jsonContent = '{}');
        $viewRenderer->setNoRender()->shouldBeCalled();
        $plugins->ViewRenderer()->willReturn($viewRenderer->reveal());
        $frontController->Plugins()->willReturn($plugins->reveal());

        $httpResponse->setHeader('Content-type', $response->headers->get('Content-Type'), true)->shouldBeCalled();
        $httpResponse->setHttpResponseCode($statusCode)->shouldBeCalled();
        $httpResponse->setBody($jsonContent)->shouldBeCalled();

        $result = $this->apiJsonResponse->sendJsonResponse(
            $frontController->reveal(),
            $httpResponse->reveal(),
            $response->reveal()
        );

        self::assertSame($httpResponse->reveal(), $result);
    }
}
