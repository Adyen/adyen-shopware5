<?php

use Shopware\Components\CSRFWhitelistAware;
use Shopware\Components\Logger;

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps, Generic.Files.LineLength.TooLong
class Shopware_Controllers_Frontend_Transparent extends Shopware_Controllers_Frontend_Payment implements CSRFWhitelistAware
{

    const ALLOWED_PARAMS = ['MD', 'PaRes', 'payload', 'redirectResult'];

    /**
     * @var Logger
     */
    private $logger;

    public function preDispatch()
    {
        $this->logger = $this->get('adyen_payment.logger');
    }

    public function getWhitelistedCSRFActions()
    {
        return ['redirect'];
    }

    /**
     * Transparent redirect to solve 3DS1 issue same site cookie policy issue
     * Used to fetch POST return data and redirect locally to allow session usage
     * https://github.com/Adyen/adyen-shopware5/issues/72
     */
    public function redirectAction()
    {
        $redirectUrl = Shopware()->Router()->assemble([
            'controller' => 'process',
            'action' => 'return',
        ]);

        $this->View()->assign('redirectUrl', $redirectUrl);
        $this->View()->assign('redirectParams', $this->retrieveParams());
        $this->logger->debug('Forward incoming POST response to process/return', [
            'POST and GET parameter keys' => self::ALLOWED_PARAMS
        ]);
    }

    private function retrieveParams(): array
    {
        $params = $this->Request()->getParams();
        $result = array();
        foreach (self::ALLOWED_PARAMS as $approvedKey) {
            if (isset($params[$approvedKey])) {
                $result[$approvedKey] = $params[$approvedKey];
            }
        }
        return $result;
    }
}
