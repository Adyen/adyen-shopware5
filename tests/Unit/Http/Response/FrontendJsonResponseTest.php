<?php

declare(strict_types=1);

namespace AdyenPayment\Tests\Unit\Http\Response;

use AdyenPayment\Http\Response\ApiJsonResponse;
use AdyenPayment\Http\Response\FrontendJsonResponse;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class FrontendJsonResponseTest extends TestCase
{
    use ProphecyTrait;
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
        $response = new JsonResponse([], Response::HTTP_OK);
        $plugins = $this->prophesize(\Enlight_Plugin_Namespace_Loader::class);
        $viewRenderer = $this->prophesize(\Enlight_Controller_Plugins_ViewRenderer_Bootstrap::class);

        $viewRenderer->setNoRender()->shouldBeCalled();
        $plugins->ViewRenderer()->willReturn($viewRenderer);
        $frontController->Plugins()->willReturn($plugins);

        $httpResponse->setHeader('Content-type', $response->headers->get('Content-Type'), true)->shouldBeCalled();
        $httpResponse->setHttpResponseCode(Response::HTTP_OK)->shouldBeCalled();
        $httpResponse->setBody($response->getContent())->shouldBeCalled();

        $result = $this->apiJsonResponse->sendJsonResponse(
            $frontController->reveal(),
            $httpResponse->reveal(),
            $response
        );

        self::assertSame($httpResponse->reveal(), $result);
    }
}
