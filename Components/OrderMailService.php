<?php

namespace AdyenPayment\Components;

use Shopware\Components\Model\ModelManager;

class OrderMailService
{
    /**
     * @var ModelManager
     */
    private $modelManager;
    /**
     * @var BasketService
     */
    private $basketService;

    public function __construct(
        ModelManager $modelManager,
        BasketService $basketService
    ) {
        $this->modelManager = $modelManager;
        $this->basketService = $basketService;
    }

    /**
     * Sends the mail after a payment is confirmed
     */
    public function sendOrderConfirmationMail($orderNumber)
    {
        $order = $this->basketService->getOrderByOrderNumber($orderNumber);
        if (!$order) {
            return;
        }

        $paymentInfoRepository = $this->modelManager->getRepository(\AdyenPayment\Models\PaymentInfo::class);
        /** @var \AdyenPayment\Models\PaymentInfo $paymentInfo */
        $paymentInfo = $paymentInfoRepository->findOneBy([
            'orderId' => $order->getId()
        ]);

        if (!$paymentInfo) {
            return;
        }

        $variables = json_decode($paymentInfo->getOrdermailVariables(), true);

        if (is_array($variables)) {
            $sOrder = Shopware()->Modules()->Order();
            $sOrder->sUserData = $variables;
            $sOrder->sendMail($variables);
        }

        $paymentInfo->setOrdermailVariables(null);
        $this->modelManager->persist($paymentInfo);
        $this->modelManager->flush($paymentInfo);
    }
}
