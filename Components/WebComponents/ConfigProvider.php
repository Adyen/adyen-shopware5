<?php

declare(strict_types=1);

namespace AdyenPayment\Components\WebComponents;

interface ConfigProvider
{
    public function __invoke(ConfigContext $context): array;
}
