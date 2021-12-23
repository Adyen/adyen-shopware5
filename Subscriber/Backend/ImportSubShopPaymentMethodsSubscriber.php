<?php

declare(strict_types=1);

namespace AdyenPayment\Subscriber\Backend;

use AdyenPayment\Dbal\Writer\Payment\PaymentMeansSubShopsWriterInterface;
use AdyenPayment\Import\PaymentMethodImporterInterface;
use AdyenPayment\Rule\AdyenApi\MainShopConfigRule;
use Doctrine\Persistence\ObjectRepository;
use Enlight\Event\SubscriberInterface;
use Shopware\Models\Shop\Shop;
use Symfony\Component\HttpFoundation\Response;

final class ImportSubShopPaymentMethodsSubscriber implements SubscriberInterface
{
    private const SAVE_VALUES_ACTION = 'saveValues';
    private ObjectRepository $shopRepository;
    private MainShopConfigRule $mainShopConfigRuleChain;
    private PaymentMeansSubShopsWriterInterface $paymentMeansSubShopsWriter;
    private PaymentMethodImporterInterface $paymentMethodImporter;

    public function __construct(
        ObjectRepository $shopRepository,
        MainShopConfigRule $mainShopConfigRule,
        PaymentMeansSubShopsWriterInterface $paymentMeansSubShopsWriter,
        PaymentMethodImporterInterface $paymentMethodImporter
    ) {
        $this->shopRepository = $shopRepository;
        $this->mainShopConfigRuleChain = $mainShopConfigRule;
        $this->paymentMeansSubShopsWriter = $paymentMeansSubShopsWriter;
        $this->paymentMethodImporter = $paymentMethodImporter;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'Enlight_Controller_Action_PostDispatch_Backend_Config' => '__invoke',
        ];
    }

    public function __invoke(\Enlight_Event_EventArgs $args): void
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
        $shop = $this->shopRepository->findBy([], ['id' => 'desc'], 1)[0] ?? null;
        if (null === $shop) {
            return;
        }

        $mainShop = $this->shopRepository->find(1);
        if (($this->mainShopConfigRuleChain)($shop, $mainShop)) {
            $this->paymentMeansSubShopsWriter->registerAdyenPaymentMethodForSubShop($shop->getId());

            return;
        }

        $this->paymentMethodImporter->importForShop($shop);
    }

    private function isNewSubShopAdded($id, $response, string $action): bool
    {
        return null === $id
            && Response::HTTP_OK === $response->getHttpResponseCode()
            && self::SAVE_VALUES_ACTION === $action;
    }
}
