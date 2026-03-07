<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\EshopCommunity\Internal\Framework\Module\Install\Service;

interface ModuleConfigurationInstallerInterface
{
    public function install(string $moduleSourcePath): void;

    public function uninstall(string $moduleSourcePath): void;

    public function uninstallById(string $moduleId): void;

    public function isInstalled(string $moduleSourcePath): bool;
}
