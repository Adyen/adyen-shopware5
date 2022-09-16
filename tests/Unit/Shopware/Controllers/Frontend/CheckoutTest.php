<?php

declare(strict_types=1);

namespace AdyenPayment\Tests\Unit\Shopware\Controllers\Frontend;

use Enlight_Template_Manager;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Shopware\Components\Cart\BasketHelperInterface;
use Shopware\Components\Cart\ProportionalTaxCalculatorInterface;
use Shopware\Components\Cart\Struct\Price;
use Shopware\Components\CSRFGetProtectionAware;
use Shopware\Models\Shop\Currency;
use Shopware\Models\Shop\Shop;
use Symfony\Component\DependencyInjection\Container;

class CheckoutTest extends TestCase
{
    use ProphecyTrait;

//    private MockObject $checkoutController;
//    private Container $container;
    /** @var ObjectProphecy|\sAdmin  */
    private $admin;
    /** @var ObjectProphecy|\sBasket  */
    private $basket;
    /** @var \Enlight_Components_Session_Namespace|ObjectProphecy  */
    private $session;
    /** @var ObjectProphecy|Container  */
    private $container;
    /** @var Enlight_Template_Manager|ObjectProphecy  */
    private $engine;
    /** @var \Enlight_View_Default  */
    private $view;
    private $checkoutController;


    protected function setUp(): void
    {
        $this->admin = $this->prophesize(\sAdmin::class);
        $this->basket = $this->prophesize(\sBasket::class);
        $this->session = $this->prophesize(\Enlight_Components_Session_Namespace::class);
        $this->container = $this->prophesize(Container::class);
        $this->engine = $this->prophesize(Enlight_Template_Manager::class);
        $this->view = new \Enlight_View_Default($this->engine->reveal());
        $this->checkoutController = new MockCheckout();
    }

    /** @test */
    public function it_is_csrf_get_protection_aware(): void
    {
        $this->assertInstanceOf(CSRFGetProtectionAware::class, $this->checkoutController);
    }

    /** @test */
    public function it_returns_complete_basket_data_to_view(): void
    {
        $this->markTestIncomplete();
//        $this->view->setScope(['test']);
//        $this->view->assign('sUserData', ['additional' => ['countryShipping' => null]]);

        $this->checkoutController->setAdyenMockProperties(
            $this->admin->reveal(),
            $this->basket->reveal(),
            $this->session->reveal(),
            $this->container->reveal()
//            $this->view
        );

        $countryList = [ $country = [
            "id" => 2,
            "name" => "Deutschland",
            "iso" => "DE",
            "en" => "GERMANY",
            "description" => "",
            "position" => 1,
            "active" => true,
            "iso3" => "DEU",
            "taxFree" => false,
            "taxFreeForVatId" => false,
            "vatIdCheck" => false,
            "displayStateSelection" => false,
            "requiresStateSelection" => false,
            "allowShipping" => true,
            "states" => [],
            "areaId" => 1,
            "attributes" => [],
            "countryname" => "Deutschland",
            "countryiso" => "DE",
            "countryen" => "GERMANY",
            "taxfree" => false,
            "taxfree_ustid" => false,
            "taxfree_ustid_checked" => false,
            "display_state_in_registration" => false,
            "force_state_in_registration" => false,
            "areaID" => 1,
            "allow_shipping" => true,
            "flag" => false
        ]];

        $this->admin->sGetCountryList()->willReturn($countryList);
//        $this->admin->sGetCountryList()->shouldBeCalledOnce();

        $shop = new Shop();
        $currency = new Currency();
        $currency->setCurrency('EUR');
        $currency->setFactor(2);
        $shop->setCurrency($currency);
        $shop = $this->container->get('shop')->willReturn($shop);

        $positions = [
            new Price(
                (float) $price = ($endPrice = 121.00) * ($quantity = 2),
                (float) $netPrice = ($netPrice = 100.00 * $quantity),
                (float) $taxRate = 21.0,
                null
            )
        ];
        $basketHelper = $this->prophesize(BasketHelperInterface::class);
        $basketHelper->getPositionPrices(Argument::cetera())->willReturn($positions);
        $positions = $this->container->get(BasketHelperInterface::class)->willReturn($basketHelper);

        $proportionalTaxCalculator = $this->prophesize(ProportionalTaxCalculatorInterface::class);
        $taxCalculator = $this->container->get('shopware.cart.proportional_tax_calculator')
            ->willReturn($proportionalTaxCalculator);
        $proportionalTaxCalculator->hasDifferentTaxes(Argument::cetera())
            ->willReturn(true);

        $configComponent = $this->prophesize(\Shopware_Components_Config::class);
        $config = $this->container->get(\Shopware_Components_Config::class)
            ->willReturn($configComponent);

        $basketResult = $this->checkoutController->getBasket();
    }
}

class MockCheckout extends \Shopware_Controllers_Frontend_Checkout
{

    public function setAdyenMockProperties($admin, $basket, $session, $container, $view): void
    {
        $this->admin = $admin;
        $this->basket = $basket;
        $this->session = $session;
        $this->container = $container;
        $this->view = $view;
    }
}
