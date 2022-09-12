<?php

declare(strict_types=1);

namespace AdyenPayment\Http\Validator\Notification;

use Adyen\AdyenException;
use Adyen\Util\HmacSignature;
use AdyenPayment\Components\Configuration;
use AdyenPayment\Exceptions\InvalidHmacException;

class HmacValidator implements NotificationValidatorInterface
{
    /** @var HmacSignature */
    private $hmacSignatureService;

    /** @var Configuration */
    private $configuration;

    public function __construct(HmacSignature $hmacSignatureService, Configuration $configuration)
    {
        $this->hmacSignatureService = $hmacSignatureService;
        $this->configuration = $configuration;
    }

    /**
     * @throws InvalidHmacException
     */
    public function validate(array $notifications): void
    {
        foreach ($notifications as $notificationItem) {
            try {
                $params = $notificationItem['NotificationRequestItem'] ?? [];
                $hmacCheck = $this->hmacSignatureService->isValidNotificationHMAC(
                    $this->configuration->getNotificationHmac(),
                    $params
                );
                if (!$hmacCheck) {
                    throw InvalidHmacException::withHmacKey($params['additionalData']['hmacSignature'] ?? '');
                }
            } catch (AdyenException $exception) {
                throw InvalidHmacException::fromAdyenException($exception);
            }
        }
    }
}
