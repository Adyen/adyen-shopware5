<?php

namespace AdyenPayment\Components\Integration\PaymentProcessors;

use Adyen\Core\BusinessLogic\Domain\Checkout\PaymentRequest\Factory\PaymentRequestBuilder;
use Adyen\Core\BusinessLogic\Domain\Checkout\PaymentRequest\Models\AdditionalData\AdditionalData;
use Adyen\Core\BusinessLogic\Domain\Checkout\PaymentRequest\Models\AdditionalData\EnhancedSchemeData;
use Adyen\Core\BusinessLogic\Domain\Checkout\PaymentRequest\Models\AdditionalData\ItemDetailLine;
use Adyen\Core\BusinessLogic\Domain\Checkout\PaymentRequest\Models\StartTransactionRequestContext;
use Adyen\Core\BusinessLogic\Domain\Integration\Processors\PaymentRequest\L2L3DataProcessor as BaseL2L3DataProcessor;
use Adyen\Core\BusinessLogic\Domain\Payment\Services\PaymentService;
use Exception;
use Shopware\Models\Country\Country;
use Shopware\Models\Country\Repository;

/**
 * Class L2L3DataProcessor
 *
 * @package AdyenPayment\Components\Integration\PaymentProcessors
 */
class L2L3DataProcessor implements BaseL2L3DataProcessor
{
    /**
     * @var PaymentService
     */
    private $paymentService;
    /**
     * @var Repository
     */
    private $countryRepository;

    /**
     * @param PaymentService $paymentService
     * @param Repository $countryRepository
     */
    public function __construct(PaymentService $paymentService, Repository $countryRepository)
    {
        $this->paymentService = $paymentService;
        $this->countryRepository = $countryRepository;
    }

    /**
     * @param PaymentRequestBuilder $builder
     * @param StartTransactionRequestContext $context
     *
     * @return void
     *
     * @throws Exception
     */
    public function process(PaymentRequestBuilder $builder, StartTransactionRequestContext $context): void
    {
        $basket = $context->getCheckoutSession()->get('basket');
        $user = $context->getCheckoutSession()->get('user');
        $country = $this->getCountryById($user['shippingaddress']['countryId']);

        if (!$this->shouldSyncL2L3Data((string)$context->getPaymentMethodCode())) {
            return;
        }

        $additionalData = new AdditionalData(
            null,
            new EnhancedSchemeData(
                $basket['AmountNumeric'] - $basket['AmountNetNumeric'],
                $user['additional']['user']['id'] ?? '',
                $basket['sShippingcostsWithTax'] ?? '',
                '',
                (new \DateTime())->format('dMy'),
                '',
                $user['shippingaddress']['state'] ?? '',
                $country ? $country->getIso() : '',
                $user['shippingaddress']['zip'] ?? '',
                $this->getDetails($basket['content']))
        );

        $builder->setAdditionalData($additionalData);
    }

    /**
     * @param string $code
     *
     * @return bool
     *
     * @throws Exception
     */
    private function shouldSyncL2L3Data(string $code): bool
    {
        $creditCardConfig = $this->paymentService->getPaymentMethodByCode($code);

        if ($creditCardConfig) {
            return $creditCardConfig->getAdditionalData() !== null
                && $creditCardConfig->getAdditionalData()->isSendBasket();
        }

        return false;
    }

    /**
     * @param array $basketContent
     *
     * @return ItemDetailLine[]
     */
    private function getDetails(array $basketContent): array
    {
        $details = [];

        foreach ($basketContent as $item) {
            $details[] = new ItemDetailLine(
                $item['additional_details']['articleName'] ?? '',
                $item['additional_details']['ean'] ?? '',
                $item['quantity'] ?? 0,
                '',
                $item['additional_details']['price'] ?? 0,
                '',
                '',
                ''
            );
        }

        return $details;
    }

    /**
     * @param string $id
     *
     * @return Country|null
     */
    private function getCountryById(string $id): ?Country
    {
        $country = $this->countryRepository->getCountryQuery($id)->getResult();

        return $country[0] ?? null;
    }
}
