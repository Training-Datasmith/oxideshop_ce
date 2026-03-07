<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\EshopCommunity\Internal\Framework\Module\Path;

class ModuleAssetsPathResolverBridge implements ModuleAssetsPathResolverInterface
{
    public function __construct(private readonly ModuleAssetsPathResolverInterface $moduleAssetsPathResolver)
    {
    }

    public function getAssetsPath(string $moduleId): string
    {
        return $this->moduleAssetsPathResolver->getAssetsPath($moduleId);
    }
}
