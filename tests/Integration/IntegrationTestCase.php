<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\EshopCommunity\Tests\Integration;

use OxidEsales\EshopCommunity\Core\Di\ContainerFacade;
use OxidEsales\EshopCommunity\Internal\Framework\Cache\ShopCacheCleanerInterface;
use OxidEsales\EshopCommunity\Tests\ContainerTrait;
use OxidEsales\EshopCommunity\Tests\DatabaseTrait;
use OxidEsales\EshopCommunity\Tests\FilesystemTrait;
use PHPUnit\Framework\TestCase;

class IntegrationTestCase extends TestCase
{
    use ContainerTrait;
    use DatabaseTrait;
    use FilesystemTrait;

    public function setUp(): void
    {
        parent::setUp();

        $this->beginTransaction();
    }

    public function tearDown(): void
    {
        $this->rollBackTransaction();

        parent::tearDown();
    }
}
