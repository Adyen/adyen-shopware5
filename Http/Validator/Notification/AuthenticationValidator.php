<?php

declare(strict_types=1);

namespace AdyenPayment\Http\Validator\Notification;

use AdyenPayment\Components\Configuration;
use AdyenPayment\Exceptions\InvalidAuthenticationException;

class AuthenticationValidator implements NotificationValidatorInterface
{
    /**
     * @var Configuration
     */
    private $configuration;

    public function __construct(Configuration $configuration)
    {
        $this->configuration = $configuration;
    }

    /**
     * @throws InvalidAuthenticationException
     */
    public function validate(array $notifications)
    {
        $authUsername = $_SERVER['PHP_AUTH_USER'] ?? $_SERVER['HTTP_PHP_AUTH_USER'] ?? '';
        $authPassword = $_SERVER['PHP_AUTH_PW'] ?? $_SERVER['HTTP_PHP_AUTH_PW'] ?? '';

        if (!$authUsername || !$authPassword) {
            throw InvalidAuthenticationException::missingAuthentication();
        }

        if ($this->configuration->getNotificationAuthUsername() !== $authUsername
            || $this->configuration->getNotificationAuthPassword() !== $authPassword
        ) {
            throw InvalidAuthenticationException::invalidCredentials();
        }
    }
}
