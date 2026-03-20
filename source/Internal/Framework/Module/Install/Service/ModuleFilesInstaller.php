<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Framework\Module\Install\Service;

use Oxid_Esales\Eshop_Community\Internal\Framework\Module\Install\Data_Object\Oxid_Eshop_Package;
use Oxid_Esales\Eshop_Community\Internal\Framework\Module\Meta_Data\Dao\Module_Configuration_Dao_Interface;
use Oxid_Esales\Eshop_Community\Internal\Framework\Module\Path\Module_Assets_Path_Resolver_Interface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;
class Module_Files_Installer implements Module_Files_Installer_Interface
{
    public function __construct(private readonly Filesystem $file_system_service, private readonly Module_Configuration_Dao_Interface $module_configuration_dao, private readonly Module_Assets_Path_Resolver_Interface $module_assets_path_resolver)
    {
    }
    public function install(Oxid_Eshop_Package $package): void
    {
        $symlink_file = $this->get_module_assets_path($package);
        $modules_assets_directory = Path::get_directory($symlink_file);
        $relative_path_to_package_assets = Path::make_relative(Path::join($package->get_package_path(), '/assets'), $modules_assets_directory);
        $this->file_system_service->symlink($relative_path_to_package_assets, $symlink_file, true);
    }
    public function uninstall(Oxid_Eshop_Package $package): void
    {
        $this->file_system_service->remove($this->get_module_assets_path($package));
    }
    public function is_installed(Oxid_Eshop_Package $package): bool
    {
        return is_link($this->get_module_assets_path($package));
    }
    private function get_module_assets_path(Oxid_Eshop_Package $package): string
    {
        return $this->module_assets_path_resolver->get_assets_path($this->get_module_id($package));
    }
    private function get_module_id(Oxid_Eshop_Package $package): string
    {
        return $this->module_configuration_dao->get($package->get_package_path())->get_id();
    }
}