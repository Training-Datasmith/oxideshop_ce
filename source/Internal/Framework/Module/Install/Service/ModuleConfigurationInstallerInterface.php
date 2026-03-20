<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Framework\Module\Install\Service;

interface Module_Configuration_Installer_Interface
{
    public function install(string $module_source_path): void;
    public function uninstall(string $module_source_path): void;
    public function uninstall_by_id(string $module_id): void;
    public function is_installed(string $module_source_path): bool;
}