<?php

namespace AdyenPayment\Subscriber;

use Adyen\Core\BusinessLogic\Domain\Multistore\StoreContext;
use Adyen\Core\BusinessLogic\Domain\TransactionHistory\Services\TransactionDetailsService;
use Adyen\Core\BusinessLogic\TransactionLog\Services\TransactionLogService;
use Adyen\Core\Infrastructure\ServiceRegister;
use AdyenPayment\Repositories\Wrapper\OrderRepository;
use Enlight\Event\SubscriberInterface;
use Enlight_Event_EventArgs;
use Exception;
use Shopware\Models\Order\Order;

/**
 * Class EmailSubscriber
 *
 * @package AdyenPayment\Subscriber
 */
class EmailSubscriber implements SubscriberInterface
{
    /**
     * @var TransactionLogService
     */
    private $transactionDetailsService;

    /**
     * @inheritDoc
     */
    public static function getSubscribedEvents(): array
    {
        return [
            'TemplateMail_CreateMail_MailContext' => '__invoke',
        ];
    }

    /**
     * @param Enlight_Event_EventArgs $args
     *
     * @return mixed
     *
     * @throws Exception
     */
    public function __invoke(Enlight_Event_EventArgs $args)
    {
        $mail = $args->get('mailModel');
        $content = $mail->getContent();
        $result = $args->getReturn();
        $orderId = $result['sOrder']['orderID'];
        $order = $this->getOrderRepository()->getOrderById((int)$orderId);

        if (!$order) {
            return $result;
        }

        if (strpos($content, '{$adyen_payment_link}') !== false &&
            $adyenPaymentLink = $this->getAdyenPaymentLink($order)) {
            $result['adyen_payment_link'] = $adyenPaymentLink;
        }

        return $result;
    }

    /**
     * @return OrderRepository
     */
    private function getOrderRepository(): OrderRepository
    {
        return Shopware()->Container()->get(OrderRepository::class);
    }

    /**
     * @return TransactionDetailsService
     */
    private function getTransactionDetailsService(): TransactionDetailsService
    {
        if ($this->transactionDetailsService === null) {
            $this->transactionDetailsService = ServiceRegister::getService(TransactionDetailsService::class);
        }

        return $this->transactionDetailsService;
    }

    /**
     * @param Order $order
     *
     * @return string|null
     *
     * @throws Exception
     */
    private function getAdyenPaymentLink(Order $order): ?string
    {
        $transactionDetails = StoreContext::doWithStore(
            (string)$order->getShop()->getId(),
            [$this->getTransactionDetailsService(), 'getTransactionDetails'],
            [$order->getTemporaryId(), (string)$order->getShop()->getId()]
        );

        if (empty($transactionDetails)) {
            return null;
        }

        $lastDetail = end($transactionDetails);

        if (empty($lastDetail['paymentLink'])) {
            return null;
        }

        return $lastDetail['paymentLink'];
    }
}
