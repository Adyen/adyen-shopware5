<?php

declare(strict_types=1);

namespace MeteorAdyen\Components\Manager;

use Doctrine\ORM\EntityManagerInterface;
use Enlight_Components_Session_Namespace;
use MeteorAdyen\MeteorAdyen;

/**
 * Class AdyenManager
 * @package MeteorAdyen\Components\Manager
 */
class AdyenManager
{
    /**
     * @var EntityManagerInterface
     */
    private $modelManager;

    /**
     * @var Enlight_Components_Session_Namespace
     */
    private $session;

    public function __construct(
        EntityManagerInterface $modelManager,
        Enlight_Components_Session_Namespace $session
    ) {
        $this->modelManager = $modelManager;
        $this->session = $session;
    }

    /**
     * @param $paymentData
     */
    public function storePaymentDataInSession($paymentData)
    {
        $this->session->offsetSet(MeteorAdyen::SESSION_ADYEN_PAYMENT_DATA, $paymentData);
    }

    /**
     * @return string
     */
    public function getPaymentDataSession(): string
    {
        return $this->session->offsetGet(MeteorAdyen::SESSION_ADYEN_PAYMENT_DATA);
    }

    public function unsetPaymentDataInSession()
    {
        $this->session->offsetUnset(MeteorAdyen::SESSION_ADYEN_PAYMENT);
        $this->session->offsetUnset(MeteorAdyen::SESSION_ADYEN_PAYMENT_VALID);
        $this->session->offsetUnset(MeteorAdyen::SESSION_ADYEN_PAYMENT_DATA);
    }
}
