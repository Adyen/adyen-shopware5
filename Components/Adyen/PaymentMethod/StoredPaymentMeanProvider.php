<?php

declare(strict_types=1);

namespace AdyenPayment\Components\Adyen\PaymentMethod;

use AdyenPayment\AdyenPayment;
use AdyenPayment\Collection\Payment\PaymentMeanCollection;
use AdyenPayment\Models\Payment\PaymentMean;
use Doctrine\DBAL\Connection;
use Enlight_Controller_Request_Request;

final class StoredPaymentMeanProvider implements StoredPaymentMeanProviderInterface
{
    /** @var EnrichedPaymentMeanProviderInterface */
    private $enrichedPaymentMeanProvider;

    /** @var Connection */
    private $connection;

    public function __construct(
        EnrichedPaymentMeanProviderInterface $enrichedPaymentMeanProvider,
        Connection $connection
    ) {
        $this->enrichedPaymentMeanProvider = $enrichedPaymentMeanProvider;
        $this->connection = $connection;
    }

    public function fromRequest(Enlight_Controller_Request_Request $request): ?PaymentMean
    {
        $registerPayment = $request->getParam('register', [])['payment'] ?? null;
        if (null === $registerPayment) {
            return null;
        }

        $enrichedPaymentMeans = ($this->enrichedPaymentMeanProvider)(
            PaymentMeanCollection::createFromShopwareArray($this->fetchUmbrellaMethod())
        );

        return $enrichedPaymentMeans->fetchByUmbrellaStoredMethodId($registerPayment);
    }

    private function fetchUmbrellaMethod(): array
    {
        $queryBuilder = $this->connection->createQueryBuilder();

        return $queryBuilder->select('*')
            ->from('s_core_paymentmeans')
            ->where('name = :umbrellaMethodName')
            ->setParameter(':umbrellaMethodName', AdyenPayment::ADYEN_STORED_PAYMENT_UMBRELLA_CODE)
            ->execute()
            ->fetchAll();
    }
}
