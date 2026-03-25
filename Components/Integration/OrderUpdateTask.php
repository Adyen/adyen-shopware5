<?php

namespace AdyenPayment\Components\Integration;

use Adyen\Core\BusinessLogic\Domain\Checkout\PaymentRequest\Exceptions\InvalidCurrencyCode;
use Adyen\Core\BusinessLogic\Domain\Checkout\PaymentRequest\Models\Amount\Amount;
use Adyen\Core\BusinessLogic\Domain\Checkout\PaymentRequest\Models\Amount\Currency;
use Adyen\Core\BusinessLogic\Domain\Integration\Order\OrderService;
use Adyen\Core\BusinessLogic\Domain\Multistore\StoreContext;
use Adyen\Core\BusinessLogic\Domain\ShopNotifications\Models\Events\Authorization\FailedPaymentAuthorizationEvent;
use Adyen\Core\BusinessLogic\Domain\ShopNotifications\Models\Events\Authorization\SuccessfulPaymentAuthorizationEvent;
use Adyen\Core\BusinessLogic\Domain\ShopNotifications\Models\Events\AuthorizationAdjustment\FailedAuthorizationAdjustmentEvent;
use Adyen\Core\BusinessLogic\Domain\ShopNotifications\Models\Events\AuthorizationAdjustment\SuccessfulAuthorizationAdjustmentEvent;
use Adyen\Core\BusinessLogic\Domain\ShopNotifications\Models\Events\Cancellation\FailedCancellationEvent;
use Adyen\Core\BusinessLogic\Domain\ShopNotifications\Models\Events\Cancellation\SuccessfulCancellationEvent;
use Adyen\Core\BusinessLogic\Domain\ShopNotifications\Models\Events\Capture\FailedCaptureEvent;
use Adyen\Core\BusinessLogic\Domain\ShopNotifications\Models\Events\Capture\SuccessfulCaptureEvent;
use Adyen\Core\BusinessLogic\Domain\ShopNotifications\Models\Events\Chargebacks\SuccessfulChargebackEvent;
use Adyen\Core\BusinessLogic\Domain\ShopNotifications\Models\Events\Event;
use Adyen\Core\BusinessLogic\Domain\ShopNotifications\Models\Events\Refund\FailedRefundEvent;
use Adyen\Core\BusinessLogic\Domain\ShopNotifications\Models\Events\Refund\SuccessfulRefundEvent;
use Adyen\Core\BusinessLogic\Domain\ShopNotifications\Services\ShopNotificationService;
use Adyen\Core\BusinessLogic\Domain\TransactionHistory\Exceptions\InvalidMerchantReferenceException;
use Adyen\Core\BusinessLogic\Domain\Webhook\Models\Webhook;
use Adyen\Core\BusinessLogic\Domain\Webhook\Services\WebhookSynchronizationService;
use Adyen\Core\BusinessLogic\TransactionLog\Tasks\TransactionalTask;
use Adyen\Core\Infrastructure\Serializer\Interfaces\Serializable;
use Adyen\Core\Infrastructure\Serializer\Serializer;
use Adyen\Core\Infrastructure\ServiceRegister;
use Adyen\Webhook\EventCodes;
use AdyenPayment\Components\Integration\OrderService as AdyenPaymentOrderService;
use Exception;

class OrderUpdateTask extends TransactionalTask
{
    /**
     * @var Webhook
     */
    private $webhook;

    /**
     * @var string
     */
    private $storeId;

    /**
     * @param Webhook $webhook
     */
    public function __construct(Webhook $webhook)
    {
        $this->webhook = $webhook;
        $this->storeId = StoreContext::getInstance()->getStoreId();
    }

    /**
     * @inheritDoc
     *
     * @throws Exception
     */
    public function execute(): void
    {
        StoreContext::doWithStore(
            $this->storeId,
            function () {
                $this->doExecute();
            }
        );
    }

    /**
     * @param array $array
     *
     * @return Serializable
     *
     * @throws InvalidCurrencyCode
     * @throws Exception
     */
    public static function fromArray(array $array): Serializable
    {
        return StoreContext::doWithStore($array['storeId'], static function () use ($array) {
            return new static(
                new Webhook(
                    Amount::fromInt($array['amount']['value'], Currency::fromIsoCode($array['amount']['currency'])),
                    $array['eventCode'],
                    $array['eventDate'],
                    $array['hmacSignature'],
                    $array['merchantAccountCode'],
                    $array['merchantReference'],
                    $array['pspReference'],
                    $array['paymentMethod'],
                    $array['reason'],
                    $array['success'],
                    $array['originalReference'],
                    $array['riskScore'],
                    $array['live'],
                    $array['additionalData']
                )
            );
        });
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return array(
            'amount' => [
                'value' => $this->webhook->getAmount()->getValue(),
                'currency' => $this->webhook->getAmount()->getCurrency()->getIsoCode()
            ],
            'eventCode' => $this->webhook->getEventCode(),
            'eventDate' => $this->webhook->getEventDate(),
            'hmacSignature' => $this->webhook->getHmacSignature(),
            'merchantAccountCode' => $this->webhook->getMerchantAccountCode(),
            'merchantReference' => $this->webhook->getMerchantReference(),
            'pspReference' => $this->webhook->getPspReference(),
            'paymentMethod' => $this->webhook->getPaymentMethod(),
            'reason' => $this->webhook->getReason(),
            'success' => $this->webhook->isSuccess(),
            'originalReference' => $this->webhook->getOriginalReference(),
            'riskScore' => $this->webhook->getRiskScore(),
            'storeId' => $this->storeId,
            'live' => $this->webhook->isLive(),
            'additionalData' => $this->webhook->getAdditionalData(),
        );
    }

    /**
     * @return string
     */
    public function serialize(): string
    {
        return Serializer::serialize([$this->webhook, $this->storeId]);
    }

    /**
     * @param string $serialized
     *
     * @return void
     */
    public function unserialize($serialized): void
    {
        [$this->webhook, $this->storeId] = Serializer::unserialize($serialized);
    }

    /**
     * @return Webhook
     */
    public function getWebhook(): Webhook
    {
        return $this->webhook;
    }

    /**
     * @return string
     */
    public function getStoreId(): string
    {
        return $this->storeId;
    }

    /**
     * @return void
     *
     * @throws InvalidMerchantReferenceException
     * @throws Exception
     */
    private function doExecute(): void
    {
        if ($this->checkIfOrderExists()) {
            $event = $this->getEventFromWebhook();

            if ($event) {
                $this->getShopNotificationService()->pushNotification($event);
            }

            $this->getSynchronizationService()->synchronizeChanges($this->webhook);
        }

        $this->reportProgress(100);
    }

    /**
     * @return ?Event
     */
    private function getEventFromWebhook(): ?Event
    {
        $event = $this->webhook->getEventCode();
        $success = $this->webhook->isSuccess();
        $merchantReference = $this->webhook->getMerchantReference();
        $paymentMethod = $this->webhook->getPaymentMethod();

        if ($event === EventCodes::AUTHORISATION && $success) {
            return new SuccessfulPaymentAuthorizationEvent($merchantReference, $paymentMethod);
        }

        if ($event === EventCodes::AUTHORISATION && !$success) {
            return new FailedPaymentAuthorizationEvent($merchantReference, $paymentMethod);
        }

        if ($event === EventCodes::CANCELLATION && $success) {
            return new SuccessfulCancellationEvent($merchantReference, $paymentMethod);
        }

        if ($event === EventCodes::CANCELLATION && !$success) {
            return new FailedCancellationEvent($merchantReference, $paymentMethod);
        }

        if ($event === EventCodes::CAPTURE && $success) {
            return new SuccessfulCaptureEvent($merchantReference, $paymentMethod);
        }

        if ($event === EventCodes::CAPTURE && !$success) {
            return new FailedCaptureEvent($merchantReference, $paymentMethod);
        }

        if ($event === EventCodes::REFUND && $success) {
            return new SuccessfulRefundEvent($merchantReference, $paymentMethod);
        }

        if ($event === EventCodes::REFUND && !$success) {
            return new FailedRefundEvent($merchantReference, $paymentMethod);
        }

        if ($event === EventCodes::AUTHORISATION_ADJUSTMENT && $success) {
            return new SuccessfulAuthorizationAdjustmentEvent($merchantReference, $paymentMethod);
        }

        if ($event === EventCodes::AUTHORISATION_ADJUSTMENT && !$success) {
            return new FailedAuthorizationAdjustmentEvent($merchantReference, $paymentMethod);
        }

        if ($event === EventCodes::CHARGEBACK && $success) {
            return new SuccessfulChargebackEvent($merchantReference, $paymentMethod);
        }

        return null;
    }

    /**
     * Checks if order exists by merchantReference first, then falls back to pspReference.
     *
     * @param int $retryCount
     *
     * @return bool
     *
     * @throws Exception
     */
    private function checkIfOrderExists(int $retryCount = 0): bool
    {
        $order = false;

        try {
            $order = $this->findOrder();

            while (!$order && $retryCount < 5) {
                sleep(2);
                $this->reportAlive();

                $order = $this->findOrder();
                $retryCount++;
            }
        } catch (Exception $exception) {
            $retryCount++;
            if ($retryCount < 5) {
                sleep(2);
                $this->reportAlive();

                return $this->checkIfOrderExists($retryCount);
            }

            throw $exception;
        }

        return $order;
    }

    /**
     * @return bool
     *
     * @throws Exception
     */
    private function findOrder(): bool
    {
        $orderService = $this->getOrderService();

        try {
            return $orderService->orderExists($this->webhook->getMerchantReference());
        } catch (Exception $e) {
            $pspReference = $this->webhook->getOriginalReference() !== ''
                ? $this->webhook->getOriginalReference()
                : $this->webhook->getPspReference();

            return $orderService->orderExistsByPspReference($pspReference);        }
    }

    /**
     * @return ShopNotificationService
     */
    private function getShopNotificationService(): ShopNotificationService
    {
        return ServiceRegister::getService(ShopNotificationService::class);
    }

    /**
     * @return OrderService
     */
    private function getOrderService(): OrderService
    {
        return ServiceRegister::getService(OrderService::class);
    }

    /**
     * @return WebhookSynchronizationService
     */
    private function getSynchronizationService(): WebhookSynchronizationService
    {
        return ServiceRegister::getService(WebhookSynchronizationService::class);
    }
}