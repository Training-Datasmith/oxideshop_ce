<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\EshopCommunity\Tests\Unit\Internal\Framework\Theme\Bridge;

use OxidEsales\EshopCommunity\Internal\Framework\Theme\Bridge\AdminThemeBridge;
use PHPUnit\Framework\TestCase;

final class AdminThemeBridgeTest extends TestCase
{
    public function testGetActiveTheme(): void
    {
        $this->assertSame('admin', (new AdminThemeBridge('admin'))->getActiveTheme());
    }
}
