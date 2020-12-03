<?php declare(strict_types=1);

namespace AdyenPayment\Subscriber;

use AdyenPayment\AdyenPayment;
use AdyenPayment\Components\OrderMailService;
use AdyenPayment\Models\PaymentInfo;
use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ORM\EntityRepository;
use Enlight\Event\SubscriberInterface;
use Enlight_Event_EventArgs;
use Shopware\Components\Model\ModelManager;
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
     * @var OrderMailService
     */
    private $orderMailService;

    public function __construct(
        ModelManager $modelManager,
        OrderMailService $orderMailService
    ) {
        $this->modelManager = $modelManager;
        $this->paymentInfoRepository = $this->modelManager->getRepository(PaymentInfo::class);
        $this->orderMailService = $orderMailService;
    }

    public static function getSubscribedEvents()
    {
        return [
            'Shopware_Modules_Order_SendMail_Send'                     => 'shouldStopEmailSending',
            'Enlight_Controller_Action_PostDispatch_Frontend_Checkout' => 'onCheckoutDispatch',
        ];
    }

    public function shouldStopEmailSending(Enlight_Event_EventArgs $args)
    {
        $orderId = $args->get('orderId');
        $variables = $args->get('variables');

        if (AdyenPayment::ADYEN_GENERAL_PAYMENT_METHOD === $variables['additional']['payment']['name']
            && true === Shopware()->Session()->get(AdyenPayment::SESSION_ADYEN_RESTRICT_EMAILS, true)
        ) {

            /** @var PaymentInfo $paymentInfo */
            $paymentInfo = $this->paymentInfoRepository->findOneBy([
                'orderId' => $orderId
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

        $this->orderMailService->sendOrderConfirmationMail($data['sOrderNumber']);
    }
}
