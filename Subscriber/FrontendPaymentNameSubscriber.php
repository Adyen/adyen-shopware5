<?php

namespace AdyenPayment\Subscriber;

use Adyen\AdyenException;
use Enlight\Event\SubscriberInterface;
use Enlight_Event_EventArgs;
use AdyenPayment\Components\PaymentMethodService as ShopwarePaymentMethodService;
use AdyenPayment\AdyenPayment;
use Psr\Log\LoggerInterface;
use Shopware_Controllers_Frontend_Checkout;

/**
 * Class FrontendPaymentNameSubscriber
 * @package AdyenPayment\Subscriber
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
            'Enlight_Controller_Action_PostDispatch_Frontend_Checkout' => 'checkoutFrontendPostDispatch',
        ];
    }

    /**
     * @param Enlight_Event_EventArgs $args
     */
    public function checkoutFrontendPostDispatch(Enlight_Event_EventArgs $args)
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
            $userData['additional']['payment']['name'] !== AdyenPayment::ADYEN_GENERAL_PAYMENT_METHOD) {
            return;
        }

        $adyenType = $this->shopwarePaymentMethodService->getActiveUserAdyenMethod(false);
        $adyenMethodName = $this->getSelectedAdyenMethodName($adyenType);
        if (!$adyenMethodName || empty($adyenMethodName->getName())) {
            return;
        }

        $userData['additional']['payment']['description'] = $adyenMethodName->getName();
        $userData['additional']['payment']['additionaldescription'] = $adyenMethodName->getDescription();
        $userData['additional']['payment']['image'] = $this->shopwarePaymentMethodService
            ->getAdyenImageByType($adyenType);
        $userData['additional']['payment']['type'] = $adyenType;

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
        if ($sPayment['name'] !== AdyenPayment::ADYEN_GENERAL_PAYMENT_METHOD) {
            return;
        }

        $adyenType = $this->shopwarePaymentMethodService->getActiveUserAdyenMethod(false);
        $adyenMethodName = $this->getSelectedAdyenMethodName($adyenType);
        if (!$adyenMethodName || empty($adyenMethodName->getName())) {
            return;
        }

        $sPayment['description'] = $adyenMethodName->getName();
        $sPayment['additionaldescription'] = $adyenMethodName->getDescription();
        $sPayment['image'] = $this->shopwarePaymentMethodService->getAdyenImageByType($adyenType);
        $subject->View()->assign('sPayment', $sPayment);
    }

    /**
     * @param $adyenType
     * @return \AdyenPayment\Models\PaymentMethodInfo
     */
    private function getSelectedAdyenMethodName($adyenType)
    {
        return $this->shopwarePaymentMethodService->getAdyenPaymentInfoByType($adyenType);
    }
}
