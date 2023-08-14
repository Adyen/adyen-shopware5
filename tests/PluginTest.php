<?php

namespace AdyenPayment\Tests;

use AdyenPayment\AdyenPayment as Plugin;
use Shopware\Tests\Functional\Components\Plugin\TestCase;

class PluginTest extends TestCase
{
    protected static $ensureLoadedPlugins = [
        'AdyenPayment' => []
    ];

    public function testCanCreateInstance()
    {
        /** @var Plugin $plugin */
        $plugin = Shopware()->Container()->get('kernel')->getPlugins()['AdyenPayment'];

        $this->assertInstanceOf(Plugin::class, $plugin);
    }
}
