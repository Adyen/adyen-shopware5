<?php

declare(strict_types=1);

namespace AdyenPayment\Session;

final class ErrorMessageProvider implements MessageProvider
{
    private const KEY_ERROR_MESSAGES = 'sErrorMessages';

    /** @var \Enlight_Components_Session_Namespace */
    private $session;

    public function __construct(\Enlight_Components_Session_Namespace $session)
    {
        $this->session = $session;
    }

    public function hasMessages(): bool
    {
        return (bool)$this->session->get(self::KEY_ERROR_MESSAGES);
    }

    public function add(string ...$messages): void
    {
        $this->session->offsetSet(
            self::KEY_ERROR_MESSAGES,
            array_merge(
                array_values($this->read()),
                array_values($messages)
            )
        );
    }

    public function read(): array
    {
        $messages = (array) ($this->session->offsetGet(self::KEY_ERROR_MESSAGES) ?? []);
        $this->session->offsetUnset(self::KEY_ERROR_MESSAGES);

        return $messages;
    }
}
