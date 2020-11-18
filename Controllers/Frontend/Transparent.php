<?php

use Shopware\Components\CSRFWhitelistAware;
use Shopware\Components\Logger;

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps, Generic.Files.LineLength.TooLong
class Shopware_Controllers_Frontend_Transparent extends Shopware_Controllers_Frontend_Payment implements CSRFWhitelistAware
{

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
        $allowedPostParams = ['MD', 'PaRes'];
        $redirectUrl = Shopware()->Router()->assemble([
            'controller' => 'process',
            'action' => 'return',
        ]);

        $this->View()->assign('redirectUrl', $redirectUrl);
        $this->View()->assign('postParams', $this->retrievePostParams($allowedPostParams));
        $this->logger->debug('Forward incoming POST response to process/return', [
            'POST parameter keys' => $allowedPostParams
        ]);
    }

    private function retrievePostParams(array $allowedParams): array
    {
        $params = [];
        foreach ($allowedParams as $key) {
            if (null === $value = $this->Request()->getPost($key)) {
                continue;
            }

            $params[$key] = $value;
        }

        return $params;
    }
}
