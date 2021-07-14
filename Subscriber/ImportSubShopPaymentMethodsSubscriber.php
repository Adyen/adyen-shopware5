<?php

declare(strict_types=1);

namespace AdyenPayment\Subscriber;

use AdyenPayment\Dbal\Writer\Payment\PaymentMeansSubshopsWriterInterface;
use AdyenPayment\Import\PaymentMethodImporterInterface;
use AdyenPayment\Rule\AdyenApi\MainShopConfigRule;
use Doctrine\Common\Persistence\ObjectRepository;
use Enlight\Event\SubscriberInterface;
use Shopware\Models\Shop\Shop;
use Symfony\Component\HttpFoundation\Response;

final class ImportSubShopPaymentMethodsSubscriber implements SubscriberInterface
{
    const SAVE_VALUES_ACTION = 'saveValues';

    /**
     * @var ObjectRepository
     */
    private $shopRepository;
    /**
     * @var MainShopConfigRule
     */
    private $mainShopConfigRuleChain;
    /**
     * @var PaymentMeansSubshopsWriterInterface
     */
    private $paymentMeansSubshopsWriter;
    /**
     * @var PaymentMethodImporterInterface
     */
    private $paymentMethodImporter;

    public function __construct(
        ObjectRepository $shopRepository,
        MainShopConfigRule $mainShopConfigRule,
        PaymentMeansSubshopsWriterInterface $paymentMeansSubshopsWriter,
        PaymentMethodImporterInterface $paymentMethodImporter
    )
    {
        $this->shopRepository = $shopRepository;
        $this->mainShopConfigRuleChain = $mainShopConfigRule;
        $this->paymentMeansSubshopsWriter = $paymentMeansSubshopsWriter;
        $this->paymentMethodImporter = $paymentMethodImporter;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'Enlight_Controller_Action_PostDispatch_Backend_Config' => '__invoke',
        ];
    }

    public function __invoke(\Enlight_Event_EventArgs $args)
    {
        $request = $args->get('request') ?? false;
        $response = $args->get('response') ?? false;

        if (!$request || !$response) {
            return;
        }

        if (!$this->isNewSubShopAdded($request->getParam('id'), $response, $request->getActionName())) {
            return;
        }

        /** @var Shop $shop */
        $shop = $this->shopRepository->findBy([], ['id' => 'desc'], 1);
        if (!count($shop)) {
            return;
        }

        $mainShop = $this->shopRepository->find(1);

        if (($this->mainShopConfigRuleChain)($shop[0], $mainShop)) {
            $this->paymentMeansSubshopsWriter->registerAdyenPaymentMethodForSubshop($shop->getId());
            return;
        }

        $this->paymentMethodImporter->importForShop($shop);
    }

    private function isNewSubShopAdded($id, $response, string $action): bool
    {
        return (null === $id)
            && (Response::HTTP_OK === $response->getHttpResponseCode())
            && ($this::SAVE_VALUES_ACTION === $action ?? false);
    }
}
