<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Framework\Module\Install\Service;

use Oxid_Esales\Eshop_Community\Internal\Framework\Module\Configuration\Dao\Shop_Configuration_Dao_Interface;
use Oxid_Esales\Eshop_Community\Internal\Framework\Module\Install\Data_Object\Oxid_Eshop_Package;
use Oxid_Esales\Eshop_Community\Internal\Framework\Module\Meta_Data\Dao\Module_Configuration_Dao_Interface;
use Oxid_Esales\Eshop_Community\Internal\Framework\Module\Setup\Service\Module_Activation_Service_Interface;
use Oxid_Esales\Eshop_Community\Internal\Framework\Module\State\Module_State_Service_Interface;
class Module_Installer implements Module_Installer_Interface
{
    public function __construct(private readonly Module_Installer_Interface $bootstrap_module_installer, private readonly Module_Activation_Service_Interface $module_activation_service, private readonly Module_Configuration_Dao_Interface $module_configuration_dao, private readonly Shop_Configuration_Dao_Interface $shop_configuration_dao, private readonly Module_State_Service_Interface $module_state_service)
    {
    }
    public function install(Oxid_Eshop_Package $package): void
    {
        $this->bootstrap_module_installer->install($package);
    }
    public function uninstall(Oxid_Eshop_Package $package): void
    {
        $module_configuration = $this->module_configuration_dao->get($package->get_package_path());
        $this->deactivate_module($module_configuration->get_id());
        $this->bootstrap_module_installer->uninstall($package);
    }
    public function is_installed(Oxid_Eshop_Package $package): bool
    {
        return $this->bootstrap_module_installer->is_installed($package);
    }
    private function deactivate_module(string $module_id): void
    {
        foreach ($this->shop_configuration_dao->get_all() as $shop_id => $shop_configuration) {
            if ($shop_configuration->has_module_configuration($module_id) && $this->module_state_service->is_active($module_id, $shop_id)) {
                $this->module_activation_service->deactivate($module_id, $shop_id);
            }
        }
    }
}