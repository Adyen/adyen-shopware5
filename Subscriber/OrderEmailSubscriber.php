<?php declare(strict_types=1);

namespace AdyenPayment\Subscriber;

use AdyenPayment\AdyenPayment;
use AdyenPayment\Components\OrderMailService;
use AdyenPayment\Models\Enum\PaymentMethod\SourceType;
use AdyenPayment\Models\PaymentInfo;
use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ORM\EntityRepository;
use Enlight\Event\SubscriberInterface;
use Enlight_Event_EventArgs;
use Shopware\Components\Model\ModelManager;
use Shopware\Models\Order\Order;
use Shopware_Controllers_Frontend_Checkout;

class OrderEmailSubscriber implements SubscriberInterface
{
    /**
     * @var ModelManager
     */
    private $modelManager;

    /**
     * @var ObjectRepository|EntityRepository
     */
    private $paymentInfoRepository;
    /**
     * @var ObjectRepository|EntityRepository
     */
    private $orderRepository;
    /**
     * @var OrderMailService
     */
    private $orderMailService;

    public function __construct(
        ModelManager $modelManager,
        OrderMailService $orderMailService
    ) {
        $this->modelManager = $modelManager;
        $this->paymentInfoRepository = $this->modelManager->getRepository(PaymentInfo::class);
        $this->orderRepository = $this->modelManager->getRepository(Order::class);
        $this->orderMailService = $orderMailService;
    }

    public static function getSubscribedEvents()
    {
        return [
            'Shopware_Modules_Order_SendMail_Send' => 'shouldStopEmailSending',
            'Shopware_Modules_Order_SaveOrder_ProcessDetails' => 'setPaymentInfoOrderNumber',
            'Enlight_Controller_Action_PostDispatch_Frontend_Checkout' => 'onCheckoutDispatch'
        ];
    }

    public function setPaymentInfoOrderNumber(Enlight_Event_EventArgs $args)
    {
        $orderId = $args->get('orderId');
        $paymentInfoId = Shopware()->Session()->get(AdyenPayment::SESSION_ADYEN_PAYMENT_INFO_ID);

        if (!empty($orderId)) {
            $orderNumber = $this->getOrderNumber($orderId);
            /** @var PaymentInfo $paymentInfo */
            $paymentInfo = $this->paymentInfoRepository->findOneBy([
                'id' => $paymentInfoId
            ]);

            if ($paymentInfo) {
                $paymentInfo->setOrdernumber($orderNumber);
                $this->modelManager->persist($paymentInfo);
                $this->modelManager->flush($paymentInfo);
            }
        }
        return $args->getReturn();
    }

    public function shouldStopEmailSending(Enlight_Event_EventArgs $args)
    {
        $variables = $args->get('variables');

        if ((int) $variables['additional']['payment']['source'] === SourceType::adyen()->getType()
            && true === Shopware()->Session()->get(AdyenPayment::SESSION_ADYEN_RESTRICT_EMAILS, true)
        ) {

            /** @var PaymentInfo $paymentInfo */
            $paymentInfo = $this->paymentInfoRepository->findOneBy([
                'ordernumber' => $variables['ordernumber']
            ]);

            if ($paymentInfo && empty($paymentInfo->getOrdermailVariables())) {
                $paymentInfo->setOrdermailVariables(json_encode($variables));

                $this->modelManager->persist($paymentInfo);
                $this->modelManager->flush($paymentInfo);
            }

            return false;
        }

        return null;
    }

    public function onCheckoutDispatch(Enlight_Event_EventArgs $args)
    {
        /** @var Shopware_Controllers_Frontend_Checkout $subject */
        $subject = $args->getSubject();

        if ($subject->Request()->getActionName() !== 'finish') {
            return;
        }

        $data = $subject->View()->getAssign();

        if (!$data['sOrderNumber']) {
            return;
        }

        $this->orderMailService->sendOrderConfirmationMail(strval($data['sOrderNumber']));
    }

    private function getOrderNumber($orderId)
    {
        return $this->orderRepository->findOneBy([
            'id' => $orderId
        ])->getNumber();
    }
}
