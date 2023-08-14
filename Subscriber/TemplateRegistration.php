<?php


namespace AdyenPayment\Subscriber;


use Enlight\Event\SubscriberInterface;

class TemplateRegistration implements SubscriberInterface
{
    /**
     * @var string
     */
    private $pluginDirectory;

    /**
     * @var \Enlight_Template_Manager
     */
    private $templateManager;

    /**
     * @var \Shopware_Components_Snippet_Manager
     */
    private $snippetManager;

    /**
     * @param $pluginDirectory
     * @param \Enlight_Template_Manager $templateManager
     * @param \Shopware_Components_Snippet_Manager $snippetManager
     */
    public function __construct(
        $pluginDirectory,
        \Enlight_Template_Manager $templateManager,
        \Shopware_Components_Snippet_Manager $snippetManager
    ) {
        $this->pluginDirectory = $pluginDirectory;
        $this->templateManager = $templateManager;
        $this->snippetManager = $snippetManager;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            'Enlight_Controller_Action_PreDispatch' => 'onPreDispatch'
        ];
    }

    public function onPreDispatch()
    {
        $this->templateManager->addTemplateDir($this->pluginDirectory . '/Resources/views');
        $this->snippetManager->addConfigDir($this->pluginDirectory . '/Resources/snippets');
    }
}
