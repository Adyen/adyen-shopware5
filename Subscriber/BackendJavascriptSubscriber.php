<?php

declare(strict_types=1);

namespace AdyenPayment\Subscriber;

use AdyenPayment\Models\Notification;
use Enlight\Event\SubscriberInterface;
use Enlight_Event_EventArgs;
use Shopware\Components\Model\ModelManager;

/**
 * Class BackendJavascriptSubscriber.
 */
class BackendJavascriptSubscriber implements SubscriberInterface
{
    /**
     * @var string
     */
    private $pluginDirectory;

    /**
     * @var \Doctrine\ORM\EntityRepository|\Doctrine\Persistence\ObjectRepository
     */
    private $notificationRepository;

    /**
     * BackendJavascriptSubscriber constructor.
     */
    public function __construct(
        string $pluginDirectory,
        ModelManager $modelManager
    ) {
        $this->pluginDirectory = $pluginDirectory;
        $this->notificationRepository = $modelManager->getRepository(Notification::class);
    }

    /**
     * @return string[]
     *
     * @psalm-return array{Enlight_Controller_Action_PostDispatchSecure_Backend_Order: 'onOrderPostDispatch', Enlight_Controller_Action_PostDispatchSecure_Backend_Customer: 'onCustomerPostDispatch'}
     */
    public static function getSubscribedEvents()
    {
        return [
            'Enlight_Controller_Action_PostDispatchSecure_Backend_Order' => 'onOrderPostDispatch',
            'Enlight_Controller_Action_PostDispatchSecure_Backend_Customer' => 'onCustomerPostDispatch',
        ];
    }

    public function onOrderPostDispatch(Enlight_Event_EventArgs $args): void
    {
        /** @var \Shopware_Controllers_Backend_Customer $controller */
        $controller = $args->getSubject();

        $view = $controller->View();
        $request = $controller->Request();

        $view->addTemplateDir($this->pluginDirectory.'/Resources/views');

        if ('index' === $request->getActionName()) {
            $view->extendsTemplate('backend/order/adyen_payment_method/app.js');
        }

        if ('getList' === $request->getActionName()) {
            $this->onGetList($args);
        }
    }

    public function onCustomerPostDispatch(Enlight_Event_EventArgs $args): void
    {
        /** @var \Shopware_Controllers_Backend_Customer $controller */
        $controller = $args->getSubject();

        $view = $controller->View();
        $request = $controller->Request();

        $view->addTemplateDir($this->pluginDirectory.'/Resources/views');

        if ('index' === $request->getActionName()) {
            $view->extendsTemplate('backend/customer/adyen_payment_method/app.js');
        }

        if ('getOrders' === $request->getActionName()) {
            $this->onGetList($args);
        }
    }

    private function onGetList(Enlight_Event_EventArgs $args): void
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
