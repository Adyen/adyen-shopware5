<?php

use MeteorAdyen\Components\OriginKeysService;

class Shopware_Controllers_Backend_TestAdyenApi extends Shopware_Controllers_Backend_ExtJs
{
    /** @var OriginKeysService */
    private $originKeysService;

    public function runAction()
    {
        $this->originKeysService = $this->get('meteor_adyen.components.origin_keys_service');

        try {
            $this->originKeysService->generateAndSave();
            $this->View()->assign('responseText', 'Success!');
        } catch (Exception $e) {
            $this->View()->assign('responseText', $e->getMessage());
        }
    }
}