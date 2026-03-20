<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Framework\Module\Install\Service;

use Oxid_Esales\Eshop_Community\Internal\Framework\Module\Install\Data_Object\Oxid_Eshop_Package;
interface Module_Installer_Interface
{
    public function install(Oxid_Eshop_Package $package);
    public function uninstall(Oxid_Eshop_Package $package): void;
    public function is_installed(Oxid_Eshop_Package $package): bool;
}