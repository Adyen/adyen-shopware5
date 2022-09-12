<?php

declare(strict_types=1);

namespace AdyenPayment\Subscriber;

use Enlight\Event\SubscriberInterface;
use Enlight_Template_Manager;

final class AddPluginTemplatesSubscriber implements SubscriberInterface
{
    /** @var Enlight_Template_Manager */
    private $templateManager;

    /** @var string */
    private $pluginDirectory;

    public function __construct(string $pluginDirectory, Enlight_Template_Manager $templateManager)
    {
        $this->templateManager = $templateManager;
        $this->pluginDirectory = $pluginDirectory;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'Enlight_Controller_Action_PreDispatch' => '__invoke',
        ];
    }

    public function __invoke(): void
    {
        $this->templateManager->addTemplateDir($this->pluginDirectory.'/Resources/views');
    }
}
