<?php

declare(strict_types=1);

namespace AdyenPayment\Subscriber;

use Adyen\AdyenException;
use AdyenPayment\AdyenPayment;
use AdyenPayment\Collection\Payment\PaymentMethodCollection;
use AdyenPayment\Components\Adyen\PaymentMethodService;
use AdyenPayment\Components\PaymentMethodService as ShopwarePaymentMethodService;
use AdyenPayment\Doctrine\Writer\PaymentMethodWriterInterface;
use AdyenPayment\Enricher\Payment\PaymentMethodEnricherInterface;
use AdyenPayment\Models\Enum\PaymentMethod\SourceType;
use AdyenPayment\Models\Payment\PaymentMethodType;
use Doctrine\Common\Persistence\ObjectRepository;
use Enlight\Event\SubscriberInterface;
use Enlight_Event_EventArgs;
use Shopware\Bundle\StoreFrontBundle\Struct\Attribute;
use Shopware\Models\Shop\Shop;

/**
 * Class PaymentSubscriber
 *
 * @package AdyenPayment\Subscriber
 */
class PaymentSubscriber implements SubscriberInterface
{
    /**
     * @var PaymentMethodService
     */
    protected $paymentMethodService;
    /**
     * @var ShopwarePaymentMethodService
     */
    private $shopwarePaymentMethodService;

    /**
     * @var $skipReplaceAdyenMethods
     */
    private $skipReplaceAdyenMethods = false;
    /**
     * @var PaymentMethodEnricherInterface
     */
    private $paymentMethodEnricher;
    /**
     * @var PaymentMethodWriterInterface
     */
    private $paymentMethodWriter;
    /**
     * @var ObjectRepository
     */
    private $shopRepository;

    /**
     * PaymentSubscriber constructor.
     *
     * @param PaymentMethodService $paymentMethodService
     * @param ShopwarePaymentMethodService $shopwarePaymentMethodService
     * @param PaymentMethodEnricherInterface $paymentMethodEnricher
     * @param PaymentMethodWriterInterface $paymentMethodWriter
     */
    public function __construct(
        PaymentMethodService $paymentMethodService,
        ShopwarePaymentMethodService $shopwarePaymentMethodService,
        PaymentMethodEnricherInterface $paymentMethodEnricher,
        PaymentMethodWriterInterface $paymentMethodWriter,
        ObjectRepository $shopRepository
    )
    {
        $this->paymentMethodService = $paymentMethodService;
        $this->shopwarePaymentMethodService = $shopwarePaymentMethodService;
        $this->paymentMethodEnricher = $paymentMethodEnricher;
        $this->paymentMethodWriter = $paymentMethodWriter;
        $this->shopRepository = $shopRepository;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'Shopware_Modules_Admin_GetPaymentMeans_DataFilter' => 'enrichAdyenPaymentMethods',
            'Shopware_Controllers_Frontend_Checkout::getSelectedPayment::before' => 'beforeGetSelectedPayment',
        ];
    }

    /**
     * Skip replacement of payment methods during checkPaymentAvailability validation
     */
    public function beforeGetSelectedPayment()
    {
        $this->skipReplaceAdyenMethods = true;
    }

    /**
     * Replace general Adyen payment method with dynamic loaded payment methods
     *
     * @param Enlight_Event_EventArgs $args
     *
     * @return array
     * @throws AdyenException
     */
    public function enrichAdyenPaymentMethods(Enlight_Event_EventArgs $args): array
    {
        $shopwareMethods = $args->getReturn();

        if ($this->skipReplaceAdyenMethods
            || !in_array(
                Shopware()->Front()->Request()->getActionName(),
                ['shippingPayment', 'payment']
            )
        ) {
            $this->skipReplaceAdyenMethods = false;

            return $shopwareMethods;
        }

        $shopwareMethods = array_filter($shopwareMethods, function ($method) {
            return $method['name'] !== AdyenPayment::ADYEN_GENERAL_PAYMENT_METHOD;
        });

        $paymentMethodOptions = $this->shopwarePaymentMethodService->getPaymentMethodOptions();
        if ($paymentMethodOptions['value'] == 0) {
            return $shopwareMethods;
        }

        $adyenPaymentMethods = PaymentMethodCollection::fromAdyenMethods(
            $this->paymentMethodService->getPaymentMethods(
                $paymentMethodOptions['countryCode'],
                $paymentMethodOptions['currency'],
                $paymentMethodOptions['value']
            )
        );

         $storedPaymentMethods = $adyenPaymentMethods->filterByPaymentType(PaymentMethodType::stored());
         $this->saveStoredPaymentMethods($storedPaymentMethods);

        $paymentMethodEnricher = $this->paymentMethodEnricher;

        // TODO: refactor to a collection or more clean structure
        $shopwareMethods = array_filter(array_map(static function (array $shopwareMethod)
        use (
            $adyenPaymentMethods,
            $paymentMethodEnricher
        ) {
            $source = (int)($shopwareMethod['source'] ?? null);
            if (SourceType::adyenType()->getType() !== $source) {
                return $shopwareMethod;
            }

            /** @var Attribute $attribute */
            $attribute = $shopwareMethod['attribute'];
            $typeOrId = $attribute->get(AdyenPayment::ADYEN_PAYMENT_STORED_METHOD_ID)
                ?: $attribute->get(AdyenPayment::ADYEN_PAYMENT_METHOD_LABEL);

            $paymentMethod = $adyenPaymentMethods->fetchByTypeOrId($typeOrId);
            if (!$paymentMethod) {
                return [];
            }

            return $paymentMethodEnricher->enrichPaymentMethod($shopwareMethod, $paymentMethod);
        }, $shopwareMethods));

        return $shopwareMethods;
    }

    private function saveStoredPaymentMethods(PaymentMethodCollection $storedPaymentMethods)
    {
        // Detached shop cannot be saved with ORM relation mapping
        // the actual shop entity needs to be fetched.
        $shopId = Shopware()->Shop() ? Shopware()->Shop()->getId() : 0;
        if (0 === $shopId) {
            return;
        }

        $shop = $this->shopRepository->find($shopId);
        if (null === $shop) {
            return;
        }

        foreach($storedPaymentMethods as $storedPaymentMethod) {
            $this->paymentMethodWriter->__invoke($storedPaymentMethod, $shop);
        }
    }
}
