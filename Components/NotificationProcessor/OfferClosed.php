<?php

namespace AdyenPayment\Components\NotificationProcessor;

use AdyenPayment\Components\PaymentStatusUpdate;
use AdyenPayment\Models\Event;
use AdyenPayment\Models\Notification;
use Psr\Log\LoggerInterface;
use Shopware\Components\ContainerAwareEventManager;
use Shopware\Models\Order\Status;

class OfferClosed implements NotificationProcessorInterface
{
	const EVENT_CODE = 'OFFER_CLOSED';

	/**
	 * @var LoggerInterface
	 */
	private $logger;
	/**
	 * @var ContainerAwareEventManager
	 */
	private $eventManager;
	/**
	 * @var PaymentStatusUpdate
	 */
	private $paymentStatusUpdate;

	public function __construct(
		LoggerInterface $logger,
		ContainerAwareEventManager $eventManager,
		PaymentStatusUpdate $paymentStatusUpdate
	) {
		$this->logger = $logger;
		$this->eventManager = $eventManager;
		$this->paymentStatusUpdate = $paymentStatusUpdate->setLogger($this->logger);
	}

	/**
	 * Returns boolean on whether this processor can process the Notification object
	 *
	 * @param Notification $notification
	 * @return boolean
	 */
	public function supports(Notification $notification): bool
	{
		return strtoupper($notification->getEventCode()) === self::EVENT_CODE;
	}

	/**
	 * Actual processing of the notification
	 *
	 * @param Notification $notification
	 * @throws \Doctrine\ORM\ORMException
	 * @throws \Doctrine\ORM\OptimisticLockException
	 * @throws \Doctrine\ORM\TransactionRequiredException
	 * @throws \Enlight_Event_Exception
	 */
	public function process(Notification $notification)
	{
		$order = $notification->getOrder();

		$this->eventManager->notify(
			Event::NOTIFICATION_PROCESS_OFFER_CLOSED,
			[
				'order' => $order,
				'notification' => $notification
			]
		);

		if ($notification->isSuccess()) {
			$this->paymentStatusUpdate->updateOrderStatus(
				$order,
				Status::ORDER_STATE_CANCELLED_REJECTED
			);
			$this->paymentStatusUpdate->updatePaymentStatus(
				$order,
				Status::PAYMENT_STATE_THE_PROCESS_HAS_BEEN_CANCELLED
			);
		}
	}
}
