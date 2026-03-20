<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Framework\Module\Install\Service;

use Oxid_Esales\Eshop_Community\Internal\Framework\Module\Install\Data_Object\Oxid_Eshop_Package;
class Bootstrap_Module_Installer implements Module_Installer_Interface
{
    public function __construct(private readonly Module_Files_Installer_Interface $module_files_installer, private readonly Module_Configuration_Installer_Interface $module_configuration_installer)
    {
    }
    public function install(Oxid_Eshop_Package $package): void
    {
        $this->module_files_installer->install($package);
        $this->module_configuration_installer->install($package->get_package_path());
    }
    public function uninstall(Oxid_Eshop_Package $package): void
    {
        $this->module_configuration_installer->uninstall($package->get_package_path());
        $this->module_files_installer->uninstall($package);
    }
    public function is_installed(Oxid_Eshop_Package $package): bool
    {
        return $this->module_files_installer->is_installed($package) && $this->module_configuration_installer->is_installed($package->get_package_path());
    }
}