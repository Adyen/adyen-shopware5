<?php

declare(strict_types=1);

namespace AdyenPayment\Components\WebComponents;

final class ConfigContext
{
    /** @var array */
    private $userData;

    /** @var array */
    private $basket;

    private function __construct()
    {
    }

    public static function fromCheckoutEvent(\Enlight_Controller_ActionEventArgs $args): self
    {
        $subject = $args->getSubject();
        $userData = $subject->View()->getAssign('sUserData') ?? [];
        $basket = $subject->View()->getAssign('sBasket') ?? [];

        $new = new self();
        $new->userData = $userData;
        $new->basket = $basket;

        return $new;
    }

    public function getUserData(): array
    {
        return $this->userData;
    }

    public function getBasket(): array
    {
        return $this->basket;
    }
}
