<?php

use AdyenPayment\Components\Adyen\ApiConfigValidator;
use AdyenPayment\Rule\AdyenApi\UsedFallbackConfigRuleInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\ConstraintViolationInterface;

//phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
class Shopware_Controllers_Backend_TestAdyenApi extends Shopware_Controllers_Backend_ExtJs
{
    /**
     * @var ApiConfigValidator
     */
    private $apiConfigValidator;
    /**
     * @var UsedFallbackConfigRuleInterface
     */
    private $usedFallbackConfigRule;

    public function preDispatch()
    {
        parent::preDispatch();

        $this->apiConfigValidator = $this->get('AdyenPayment\Components\Adyen\ApiConfigValidator');
        $this->usedFallbackConfigRule = $this->get('AdyenPayment\Rule\AdyenApi\UsedFallbackConfigRule');
    }

    public function runAction()
    {
        $shopId = (int) $this->request->get('shopId', 1);
        $violations = $this->apiConfigValidator->validate($shopId);
        if ($violations->count() > 0) {
            $this->response->setHttpResponseCode(Response::HTTP_BAD_REQUEST);
            $this->View()->assign('responseText', implode("\n", array_map(
                static function (ConstraintViolationInterface $violation) {
                    return $violation->getMessage();
                },
                iterator_to_array($violations)
            )));

            return;
        }

        $usedFallback = ($this->usedFallbackConfigRule)($shopId);
        $this->response->setHttpResponseCode(Response::HTTP_OK);
        $this->View()->assign('responseText', sprintf(
            '%sAdyen API connection successful.',
            $usedFallback ?  "Fallback to main shop API configuration<br />" : ''
        ));
    }
}
