<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\EshopCommunity\Internal\Framework\Module\Translation\Bridge;

use OxidEsales\EshopCommunity\Internal\Framework\Module\Translation\Locator\{
    FrontendModuleTranslationFileLocatorInterface};

class FrontendModuleTranslationFileLocatorBridge implements FrontendModuleTranslationFileLocatorBridgeInterface
{
    public function __construct(private readonly FrontendModuleTranslationFileLocatorInterface $moduleTranslationFileLocator)
    {
    }

    public function locate(string $lang): array
    {
        return $this->moduleTranslationFileLocator->locate($lang);
    }
}
