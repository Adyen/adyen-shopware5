<?php

declare(strict_types=1);

namespace AdyenPayment\Subscriber;

use Adyen\Core\BusinessLogic\Domain\Checkout\PaymentRequest\Models\PaymentMethodCode;
use Adyen\Core\BusinessLogic\Domain\Multistore\StoreContext;
use Adyen\Core\BusinessLogic\Domain\Payment\Repositories\PaymentMethodConfigRepository;
use Adyen\Core\Infrastructure\ServiceRegister;
use AdyenPayment\AdyenPayment;
use AdyenPayment\Components\PaymentMeansEnricher;
use Enlight\Event\SubscriberInterface;
use Enlight_Event_EventArgs;
use Exception;
use Shopware\Models\Customer\Customer;

/**
 * Class EnrichPaymentSubscriber
 *
 * @package AdyenPayment\Subscriber
 */
final class EnrichPaymentSubscriber implements SubscriberInterface
{
    /**
     * @var PaymentMeansEnricher
     */
    private $paymentMeansEnricher;

    public function __construct(PaymentMeansEnricher $paymentMeansEnricher)
    {
        $this->paymentMeansEnricher = $paymentMeansEnricher;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'Shopware_Modules_Admin_GetPaymentMeans_DataFilter' => '__invoke',
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     *
     * @throws Exception
     */
    public function __invoke(Enlight_Event_EventArgs $args): array
    {
        $paymentMeans = $args->getReturn();
        if (!in_array(Shopware()->Front()->Request()->getActionName(), ['shippingPayment', 'payment'], true)) {
            return $paymentMeans;
        }

        $userData = Shopware()->Modules()->Admin()->sGetUserData();
        // Remove stored payments for guest checkout
        if ((int)$userData['additional']['user']['accountmode'] === Customer::ACCOUNT_MODE_FAST_LOGIN
            || !$this->isCreditCardEnabled()) {
            $paymentMeans = $this->filterStoredPaymentMethods($paymentMeans);
        }

        return $this->paymentMeansEnricher->enrich($paymentMeans);
    }

    private function filterStoredPaymentMethods($paymentMeans): array
    {
        return array_filter(array_map(static function (array $paymentMean) {
            if ($paymentMean['name'] === AdyenPayment::STORED_PAYMENT_UMBRELLA_NAME) {
                return null;
            }

            return $paymentMean;
        }, $paymentMeans));
    }

    /**
     * @return bool
     *
     * @throws Exception
     */
    private function isCreditCardEnabled(): bool
    {
        /** @var PaymentMethodConfigRepository $repository */
        $repository = ServiceRegister::getService(PaymentMethodConfigRepository::class);

        $cardConfig = StoreContext::doWithStore(
            '' . Shopware()->Shop()->getId(),
            [$repository, 'getPaymentMethodByCode'],
            [(string)PaymentMethodCode::scheme()]
        );

        return $cardConfig !== null;
    }
}
