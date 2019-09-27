<?php


use MeteorAdyen\Components\Configuration;

class Shopware_Controllers_Frontend_Notification extends Enlight_Controller_Action
{
    /**
     * @throws Exception
     */
    public function preDispatch()
    {
        $this->Front()->Plugins()->ViewRenderer()->setNoRender();

    }

    /**
     *
     */
    public function postDispatch()
    {
        $data = $this->View()->getAssign();
        $pretty = $this->Request()->getParam('pretty', false);

        array_walk_recursive($data, static function (&$value) {
            // Convert DateTime instances to ISO-8601 Strings
            if ($value instanceof DateTime) {
                $value = $value->format(DateTime::ISO8601);
            }
        });

        $data = Zend_Json::encode($data);
        if ($pretty) {
            $data = Zend_Json::prettyPrint($data);
        }

        $this->Response()->headers->set('content-type', 'application/json', true);
        $this->Response()->setContent($data);
    }

    private function checkAuthentication()
    {
        /** @var Configuration $configuration */
        $configuration = $this->get('meteor_adyen.components.configuration');

        $authUsername = $_SERVER['PHP_AUTH_USER'];
        $authPassword = $_SERVER['PHP_AUTH_PW'];

        if ($authUsername !== $configuration->getNotificationAuthUsername() ||
            $authPassword !== $configuration->getNotificationAuthPassword()) {
            $this->View()->assign(['success' => false, 'message' => 'Invalid or missing auth']);

            return false;
        }

        return true;
    }

    public function indexAction()
    {
        if (!$this->checkAuthentication()) {
            return;
        }

        $view = $this->View();
        $view->assign('dinges', 'fkdjf');
    }
}
