<?php

declare(strict_types=1);

namespace AdyenPayment\Http\Validator\Notification;

class Chain implements NotificationValidatorInterface
{
    /**
     * @var NotificationValidatorInterface[]
     */
    private $validators;

    public function __construct(NotificationValidatorInterface ...$validators)
    {
        $this->validators = $validators;
    }

    /**
     * {@inheritdoc}
     */
    public function validate(array $notifications): void
    {
        foreach ($this->validators as $validator) {
            $validator->validate($notifications);
        }
    }
}
