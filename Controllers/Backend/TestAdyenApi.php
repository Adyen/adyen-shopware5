<?php

use AdyenPayment\AdyenApi\HttpClient\ConfigValidator;
use AdyenPayment\Rule\AdyenApi\UsedFallbackConfigRule;
use AdyenPayment\Rule\AdyenApi\UsedFallbackConfigRuleInterface;
use Shopware\Components\CacheManager;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\ConstraintViolationInterface;

//phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
class Shopware_Controllers_Backend_TestAdyenApi extends Shopware_Controllers_Backend_ExtJs
{
    /** @var ConfigValidator */
    private $apiConfigValidator;

    /** @var UsedFallbackConfigRuleInterface */
    private $usedFallbackConfigRule;

    /** @var CacheManager */
    private $cacheManager;

    public function preDispatch(): void
    {
        parent::preDispatch();
      
        $this->cacheManager = $this->get('shopware.cache_manager');
        $this->apiConfigValidator = $this->get(ConfigValidator::class);
        $this->usedFallbackConfigRule = $this->get(UsedFallbackConfigRule::class);
    }

    public function runAction(): void
    {
        $shopId = (int) $this->request->get('shopId', 1);
        $this->cacheManager->clearConfigCache();

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
            $usedFallback ? "Fallback to main shop API configuration<br />" : ''
        ));
    }
}
