<?php

use Adyen\Core\BusinessLogic\CheckoutAPI\CheckoutAPI;
use Adyen\Core\BusinessLogic\CheckoutAPI\Donations\Request\MakeDonationRequest;
use AdyenPayment\Components\ErrorMessageProvider;
use AdyenPayment\Controllers\Common\AjaxResponseSetter;

/**
 * Class Shopware_Controllers_Frontend_AdyenDonations
 */
class Shopware_Controllers_Frontend_AdyenDonations extends Shopware_Controllers_Frontend_Payment
{
    use AjaxResponseSetter {
        AjaxResponseSetter::preDispatch as protected ajaxResponseSetterPreDispatch;
    }

    /**
     * @var ErrorMessageProvider
     */
    private $errorMessageProvider;
    /**
     * @var Shopware_Components_Snippet_Manager
     */
    private $snippets;

    /**
     * @param $request
     * @param $response
     *
     * @return void
     *
     * @throws Enlight_Event_Exception
     * @throws Enlight_Exception
     * @throws Exception
     */
    public function initController($request, $response): void
    {
        $this->Front()->Plugins()->JsonRequest()
                ->setParseInput();

        parent::initController($request, $response);
    }

    /**
     * @return void
     * @throws Exception
     */
    public function preDispatch(): void
    {
        $this->ajaxResponseSetterPreDispatch();
        $this->errorMessageProvider = $this->get(ErrorMessageProvider::class);
        $this->snippets = $this->get('snippets');
    }

    public function getDonationsConfigAction(): void
    {
        $merchantReference = $this->Request()->get('merchantReference');
        $currencyFactor = Shopware()->Shop()->getCurrency()->getFactor();
        $result = CheckoutAPI::get()
            ->donation(Shopware()->Shop()->getId())
            ->getDonationSettings($merchantReference, empty($currencyFactor) ? 1 : $currencyFactor);

        $this->returnAPIResponse($result);
    }

    public function makeDonationsAction(): void
    {
        $params = $this->Request()->getParams();

        $result = CheckoutAPI::get()
                ->donation(Shopware()->Shop()->getId())
                ->makeDonation(
                        new MakeDonationRequest(
                                $params['amount']['value'] ?? '',
                                $params['amount']['currency'] ?? '',
                                $this->Request()->get('merchantReference')
                        )
                );

        if (!$result->isSuccessful()) {
            $this->errorMessageProvider->add(
                    $this->snippets->getNamespace('frontend/adyen/checkout')->get(
                            'donations/adyen/fail',
                            'Donation failed.'
                    )
            );
        }

        if ($result->isSuccessful()) {
            $this->errorMessageProvider->addSuccessMessage(
                    $this->snippets->getNamespace('frontend/adyen/checkout')->get(
                            'donations/adyen/success',
                            'Donation successfully made.'
                    )
            );
        }

        $this->returnAPIResponse($result);
    }
}
