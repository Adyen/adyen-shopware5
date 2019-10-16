<?php

namespace MeteorAdyen\Subscriber;

use Adyen\AdyenException;
use Enlight\Event\SubscriberInterface;
use Enlight_Event_EventArgs;
use MeteorAdyen\Components\PaymentMethodService as ShopwarePaymentMethodService;
use MeteorAdyen\MeteorAdyen;
use Psr\Log\LoggerInterface;
use Shopware_Controllers_Frontend_Checkout;

/**
 * Class FrontendPaymentNameSubscriber
 * @package MeteorAdyen\Subscriber
 */
class FrontendPaymentNameSubscriber implements SubscriberInterface
{
    /**
     * @var ShopwarePaymentMethodService
     */
    private $shopwarePaymentMethodService;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * CheckoutSubscriber constructor.
     * @param ShopwarePaymentMethodService $shopwarePaymentMethodService
     * @param LoggerInterface $logger
     */
    public function __construct(
        ShopwarePaymentMethodService $shopwarePaymentMethodService,
        LoggerInterface $logger
    ) {
        $this->shopwarePaymentMethodService = $shopwarePaymentMethodService;
        $this->logger = $logger;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            'Enlight_Controller_Action_PostDispatch_Frontend_Checkout' => 'CheckoutFrontendPostDispatch',
        ];
    }

    /**
     * @param Enlight_Event_EventArgs $args
     */
    public function CheckoutFrontendPostDispatch(Enlight_Event_EventArgs $args)
    {
        $this->rewriteConfirmPaymentInfo($args);
        $this->rewriteFinishPaymentInfo($args);
    }

    /**
     * @param Enlight_Event_EventArgs $args
     */
    private function rewriteConfirmPaymentInfo(Enlight_Event_EventArgs $args)
    {
        /** @var Shopware_Controllers_Frontend_Checkout $subject */
        $subject = $args->getSubject();

        if (!in_array($subject->Request()->getActionName(), ['confirm'])) {
            return;
        }

        $userData = $subject->View()->getAssign('sUserData');
        if (!$userData['additional'] ||
            !$userData['additional']['payment'] ||
            $userData['additional']['payment']['name'] != MeteorAdyen::ADYEN_GENERAL_PAYMENT_METHOD) {
            return;
        }

        $adyenMethodName = $this->getSelectedAdyenMethodName();
        if (!$adyenMethodName || empty($adyenMethodName)) {
            return;
        }

        $userData['additional']['payment']['description'] = $adyenMethodName;
        $subject->View()->assign('sUserData', $userData);
    }

    /**
     * @param Enlight_Event_EventArgs $args
     */
    private function rewriteFinishPaymentInfo(Enlight_Event_EventArgs $args)
    {
        /** @var Shopware_Controllers_Frontend_Checkout $subject */
        $subject = $args->getSubject();

        if (!in_array($subject->Request()->getActionName(), ['finish'])) {
            return;
        }

        $sPayment = $subject->View()->getAssign('sPayment');
        if ($sPayment['name'] != MeteorAdyen::ADYEN_GENERAL_PAYMENT_METHOD) {
            return;
        }

        $adyenMethodName = $this->getSelectedAdyenMethodName();
        if (!$adyenMethodName || empty($adyenMethodName)) {
            return;
        }

        $sPayment['description'] = $adyenMethodName;
        $subject->View()->assign('sPayment', $sPayment);
    }

    /**
     * @return string
     */
    private function getSelectedAdyenMethodName()
    {
        try {
            $selectedAdyen = $this->shopwarePaymentMethodService->getActiveUserAdyenMethod(false);
            return $this->shopwarePaymentMethodService->getAdyenPaymentDescriptionByType($selectedAdyen);
        } catch (AdyenException $ex) {
            $this->logger->notice('Fail loading Adyen description', ['ex' => $ex]);
            return '';
        }
    }
}
