<?php

declare(strict_types=1);

namespace MeteorAdyen\Components\Manager;

use Doctrine\ORM\EntityManagerInterface;
use Enlight_Components_Session_Namespace;

/**
 * Class AdyenManager
 * @package MeteorAdyen\Components\Manager
 */
class AdyenManager
{
    const paymentDataSession = 'adyenPaymentData';

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
        $this->session->offsetSet(self::paymentDataSession, $paymentData);
    }

    /**
     * @return string
     */
    public function getPaymentDataSession(): string
    {
        return $this->session->offsetGet(self::paymentDataSession) ?? '';
    }

    public function unsetPaymentDataInSession()
    {
        $this->session->offsetUnset('adyenPayment');
        $this->session->offsetUnset('adyenPaymentData');
    }
}
