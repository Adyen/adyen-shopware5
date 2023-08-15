<?php

namespace AdyenPayment\Components\Integration;

use Adyen\Core\BusinessLogic\Domain\Webhook\Models\Webhook;
use Adyen\Core\BusinessLogic\Domain\Webhook\Services\WebhookSynchronizationService;
use Adyen\Core\BusinessLogic\Webhook\Handler\WebhookHandler;
use Adyen\Core\Infrastructure\TaskExecution\QueueService;
use AdyenPayment\Repositories\Wrapper\OrderRepository;

/**
 * Class LegacyMerchantReferenceNormalizationWebhookHandler
 *
 * @package AdyenPayment\Components\Integration
 */
class LegacyMerchantReferenceNormalizationWebhookHandler extends WebhookHandler
{
    /**
     * @var OrderRepository
     */
    private $orderRepository;

    public function __construct(
        OrderRepository $orderRepository,
        WebhookSynchronizationService $synchronizationService,
        QueueService $queueService
    ) {
        $this->orderRepository = $orderRepository;

        parent::__construct($synchronizationService, $queueService);
    }

    public function handle(Webhook $webhook): void
    {
        $legacyOrderMap = $this->orderRepository->getOrdersByNumbers([$webhook->getMerchantReference()]);
        if (array_key_exists($webhook->getMerchantReference(), $legacyOrderMap)) {
            $webhook = new Webhook(
                $webhook->getAmount(),
                $webhook->getEventCode(),
                $webhook->getEventDate(),
                $webhook->getHmacSignature(),
                $webhook->getMerchantAccountCode(),
                $legacyOrderMap[$webhook->getMerchantReference()]->getTemporaryId(),
                $webhook->getPspReference(),
                $webhook->getPaymentMethod(),
                $webhook->getReason(),
                $webhook->isSuccess(),
                $webhook->getOriginalReference(),
                $webhook->getRiskScore(),
                $webhook->isLive()
            );
        }

        parent::handle($webhook);
    }
}
