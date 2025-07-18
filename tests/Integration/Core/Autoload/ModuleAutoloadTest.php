<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\EshopCommunity\Tests\Integration\Core\Autoload;

use OxidEsales\Eshop\Core\Registry;
use OxidEsales\EshopCommunity\Core\Autoload\ModuleAutoload;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Facade\ActiveModulesDataProviderBridgeInterface;
use OxidEsales\EshopCommunity\Tests\ContainerTrait;
use OxidEsales\EshopCommunity\Tests\Integration\IntegrationTestCase;
use Psr\Log\LoggerInterface;

class ModuleAutoloadTest extends IntegrationTestCase
{
    use ContainerTrait;

    public function setUp(): void
    {
        parent::setUp();

        $this->createContainer();
    }

    public function testAutoloadIntegrationWithValidService(): void
    {
        $className = 'MockServiceClass';
        $processedClassName = strtolower(basename($className));

        $mockService = new \stdClass();
        $this->replaceService($processedClassName, $mockService);
        $this->attachContainerToContainerFactory();

        $result = ModuleAutoload::autoload($className);
        $this->assertNull($result);
    }
}
