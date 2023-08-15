<?php

declare(strict_types=1);

namespace AdyenPayment\Subscriber;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Enlight\Event\SubscriberInterface;
use Enlight_Components_Session_Namespace;
use Enlight_Event_EventArgs;

/**
 * Class LimitPercentageSurcharge
 *
 * @package AdyenPayment\Subscriber\LimitPercentageSurcharge
 */
final class LimitPercentageSurcharge implements SubscriberInterface
{
    /**
     * @var Enlight_Components_Session_Namespace
     */
    private $session;
    /**
     * @var Connection
     */
    private $connection;

    public function __construct(
            Enlight_Components_Session_Namespace $session,
            Connection $connection
    ) {
        $this->session = $session;
        $this->connection = $connection;
    }

    public static function getSubscribedEvents(): array
    {
        return [
                'Shopware_Modules_Basket_BeforeAddOrderSurchargePercent' => '__invoke',
        ];
    }

    /**
     * @param Enlight_Event_EventArgs $args
     *
     * @return void
     *
     * @throws Exception
     */
    public function __invoke(Enlight_Event_EventArgs $args): void
    {
        $surchargeParams = $args->get('surcharge');
        if (empty($surchargeParams['price'])) {
            return;
        }

        $enrichedPaymentMean = $this->session->get('adyenEnrichedPaymentMean');
        if (
                empty($enrichedPaymentMean['isAdyenPaymentMethod']) ||
                empty($enrichedPaymentMean['surchargeLimit']) ||
                (float)$enrichedPaymentMean['surchargeLimit'] > (float)$surchargeParams['price']
        ) {
            return;
        }

        $surchargeParams['price'] = (float)$enrichedPaymentMean['surchargeLimit'] * $surchargeParams['currencyFactor'];
        $surchargeParams['netprice'] = $surchargeParams['price'] / (1 + $surchargeParams['tax_rate'] / 100);
        $this->connection->insert('s_order_basket', $surchargeParams);

        $args->setProcessed(true);
    }
}
