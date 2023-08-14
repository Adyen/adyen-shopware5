<?php

declare(strict_types=1);

namespace AdyenPayment\Components;

final class ErrorMessageProvider implements MessageProvider
{
    private const ERROR_MESSAGES_SESSION_KEY = 'sErrorMessages';
    private const SUCCESS_MESSAGES_SESSION_KEY = 'sSuccessMessages';

    /** @var \Enlight_Components_Session_Namespace */
    private $session;

    public function __construct(\Enlight_Components_Session_Namespace $session)
    {
        $this->session = $session;
    }

    public function hasMessages(): bool
    {
        return (bool)$this->session->get(self::ERROR_MESSAGES_SESSION_KEY) ||
            (bool)$this->session->get(self::SUCCESS_MESSAGES_SESSION_KEY);
    }

    public function add(string ...$messages): void
    {
        $this->session->offsetSet(
            self::ERROR_MESSAGES_SESSION_KEY,
            array_merge(
                array_values($this->read()),
                array_values($messages)
            )
        );
    }

    public function addSuccessMessage(string ...$messages): void
    {
        $this->session->offsetSet(
            self::SUCCESS_MESSAGES_SESSION_KEY,
            array_merge(
                [$this->readSuccessMessages()],
                array_values($messages)
            )
        );
    }

    public function read(): array
    {
        $messages = (array)($this->session->offsetGet(self::ERROR_MESSAGES_SESSION_KEY) ?? []);
        $this->session->offsetUnset(self::ERROR_MESSAGES_SESSION_KEY);

        return $messages;
    }

    public function readSuccessMessages(): string
    {
        $messages = ($this->session->offsetGet(self::SUCCESS_MESSAGES_SESSION_KEY));
        $this->session->offsetUnset(self::SUCCESS_MESSAGES_SESSION_KEY);

        return !empty($messages) ? $messages[0] : '';
    }
}
