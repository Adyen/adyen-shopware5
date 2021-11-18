<?php

declare(strict_types=1);

namespace AdyenPayment\Session;

use Enlight_Components_Session_Namespace;

final class ErrorMessageProvider implements MessageProvider
{
    private const KEY_ERROR_MESSAGES = 'sErrorMessages';

    private Enlight_Components_Session_Namespace $session;

    public function __construct(Enlight_Components_Session_Namespace $session)
    {
        $this->session = $session;
    }

    public function hasMessages(): bool
    {
        return $this->session->has(self::KEY_ERROR_MESSAGES);
    }

    public function add(string ...$messages): void
    {
        $this->session->offsetSet(self::KEY_ERROR_MESSAGES, [...$this->read(), ...$messages]);
    }

    public function read(): array
    {
        $messages = $this->session->offsetGet(self::KEY_ERROR_MESSAGES) ?? [];
        $this->session->offsetUnset(self::KEY_ERROR_MESSAGES);

        return $messages;
    }
}
