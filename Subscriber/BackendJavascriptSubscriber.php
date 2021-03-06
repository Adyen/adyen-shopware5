<?php declare(strict_types=1);

namespace AdyenPayment\Subscriber;

use Enlight\Event\SubscriberInterface;
use Enlight_Event_EventArgs;
use AdyenPayment\Models\Notification;
use Shopware\Components\Model\ModelManager;

/**
 * Class BackendJavascriptSubscriber
 * @package AdyenPayment\Subscriber
 */
class BackendJavascriptSubscriber implements SubscriberInterface
{
    /**
     * @var string
     */
    private $pluginDirectory;

    /**
     * @var \Doctrine\Common\Persistence\ObjectRepository|\Doctrine\ORM\EntityRepository
     */
    private $notificationRepository;

    /**
     * BackendJavascriptSubscriber constructor.
     * @param string $pluginDirectory
     * @param ModelManager $modelManager
     */
    public function __construct(
        string $pluginDirectory,
        ModelManager $modelManager
    ) {
        $this->pluginDirectory = $pluginDirectory;
        $this->notificationRepository = $modelManager->getRepository(Notification::class);
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            'Enlight_Controller_Action_PostDispatchSecure_Backend_Order' => 'onOrderPostDispatch',
            'Enlight_Controller_Action_PostDispatchSecure_Backend_Customer' => 'onCustomerPostDispatch'
        ];
    }

    /**
     * @param Enlight_Event_EventArgs $args
     */
    public function onOrderPostDispatch(\Enlight_Event_EventArgs $args)
    {
        /** @var \Shopware_Controllers_Backend_Customer $controller */
        $controller = $args->getSubject();

        $view = $controller->View();
        $request = $controller->Request();

        $view->addTemplateDir($this->pluginDirectory . '/Resources/views');

        if ($request->getActionName() === 'index') {
            $view->extendsTemplate('backend/order/adyen_payment_method/app.js');
        }

        if ($request->getActionName() === 'getList') {
            $this->onGetList($args);
        }
    }

    public function onCustomerPostDispatch(\Enlight_Event_EventArgs $args)
    {
        /** @var \Shopware_Controllers_Backend_Customer $controller */
        $controller = $args->getSubject();

        $view = $controller->View();
        $request = $controller->Request();

        $view->addTemplateDir($this->pluginDirectory . '/Resources/views');

        if ($request->getActionName() === 'index') {
            $view->extendsTemplate('backend/customer/adyen_payment_method/app.js');
        }

        if ($request->getActionName() === 'getOrders') {
            $this->onGetList($args);
        }
    }

    /**
     * @param Enlight_Event_EventArgs $args
     */
    private function onGetList(Enlight_Event_EventArgs $args)
    {
        $assign = $args->getSubject()->View()->getAssign();

        $data = $assign['data'];
        foreach ($data as &$order) {
            $notification = $this->notificationRepository->findOneBy(['orderId' => $order['id']]);

            if (!$notification) {
                continue;
            }
            $order['adyen_payment_order_payment'] = $notification->getPaymentMethod();
        }

        $args->getSubject()->View()->assign('data', $data);
    }
}
