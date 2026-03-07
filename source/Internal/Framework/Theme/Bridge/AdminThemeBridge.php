<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\EshopCommunity\Internal\Framework\Theme\Bridge;

class AdminThemeBridge implements AdminThemeBridgeInterface
{
    public function __construct(private readonly string $activeThemeName)
    {
    }

    public function getActiveTheme(): string
    {
        return $this->activeThemeName;
    }
}
