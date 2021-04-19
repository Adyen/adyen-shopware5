<?php

declare(strict_types=1);

namespace AdyenPayment\Components\Manager;

use Shopware\Models\Order\Order;
use Shopware\Models\Order\Status;

interface OrderManagerInterface
{
    public function save(Order $order);
    public function updatePspReference(Order $order, string $pspReference);
    public function updatePayment(Order $order, string $pspReference, Status $paymentStatus);
}
