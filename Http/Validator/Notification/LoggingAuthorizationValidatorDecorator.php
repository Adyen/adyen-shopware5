<?php

declare(strict_types=1);

namespace AdyenPayment\Http\Validator\Notification;

use AdyenPayment\Exceptions\AuthorizationException;
use Psr\Log\LoggerInterface;

class LoggingAuthorizationValidatorDecorator implements NotificationValidatorInterface
{
    /**
     * @var NotificationValidatorInterface
     */
    private $authorizationValidator;
    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(NotificationValidatorInterface $authenticationValidator, LoggerInterface $logger)
    {
        $this->authorizationValidator = $authenticationValidator;
        $this->logger = $logger;
    }

    public function validate(array $notifications)
    {
        try {
            $this->authorizationValidator->validate($notifications);
        } catch (AuthorizationException $exception) {
            $this->logger->critical($exception->getMessage(), [
                'trace' => $exception->getTraceAsString(),
                'previous' => $exception->getPrevious(),
            ]);

            throw $exception;
        }
    }
}
