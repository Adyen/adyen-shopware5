<?php

declare(strict_types=1);

namespace AdyenPayment\Components;

use AdyenPayment\Models\PaymentInfo;
use Shopware\Components\Model\ModelManager;

class OrderMailService
{
    private ModelManager $modelManager;
    private BasketService $basketService;

    public function __construct(ModelManager $modelManager, BasketService $basketService)
    {
        $this->modelManager = $modelManager;
        $this->basketService = $basketService;
    }

    /**
     * Sends the mail after a payment is confirmed.
     */
    public function sendOrderConfirmationMail(string $orderNumber): void
    {
        $order = $this->basketService->getOrderByOrderNumber($orderNumber);
        if (!$order) {
            return;
        }

        $paymentInfoRepository = $this->modelManager->getRepository(PaymentInfo::class);
        /** @var PaymentInfo $paymentInfo */
        $paymentInfo = $paymentInfoRepository->findOneBy([
            'orderId' => $order->getId(),
        ]);

        if (!$paymentInfo || null === $paymentInfo->getOrdermailVariables()) {
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
