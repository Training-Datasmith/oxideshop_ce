<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Framework\Module\Install\Service;

use Oxid_Esales\Eshop_Community\Internal\Framework\Module\Configuration\Dao\Chain\Class_Extensions_Chain_Dao_Interface;
use Oxid_Esales\Eshop_Community\Internal\Framework\Module\Configuration\Dao\Module_Configuration_Dao_Interface;
use Oxid_Esales\Eshop_Community\Internal\Framework\Module\Configuration\Dao\Shop_Configuration_Dao_Interface;
use Oxid_Esales\Eshop_Community\Internal\Framework\Module\Configuration\Data_Object\Module_Configuration;
use Oxid_Esales\Eshop_Community\Internal\Framework\Module\Configuration\Exception\Extension_Not_In_Chain_Exception;
use Oxid_Esales\Eshop_Community\Internal\Framework\Module\Configuration\Service\{Module_Configuration_Merging_Service_Interface};
use Oxid_Esales\Eshop_Community\Internal\Framework\Module\Meta_Data\Dao\Module_Configuration_Dao_Interface as MetadataDaoInterface;
use Oxid_Esales\Eshop_Community\Internal\Transition\Utility\Basic_Context_Interface;
use Symfony\Component\Filesystem\Path;
class Module_Configuration_Installer implements Module_Configuration_Installer_Interface
{
    public function __construct(private readonly Shop_Configuration_Dao_Interface $shop_configuration_dao, private readonly Basic_Context_Interface $context, private readonly Module_Configuration_Merging_Service_Interface $module_configuration_merging_service, private readonly Metadata_Dao_Interface $metadata_module_configuration_dao, private readonly Module_Configuration_Dao_Interface $module_configuration_dao, private readonly Class_Extensions_Chain_Dao_Interface $class_extensions_chain_dao)
    {
    }
    public function install(string $module_source_path): void
    {
        $module_configuration = $this->metadata_module_configuration_dao->get($module_source_path);
        $module_configuration->set_module_source($this->get_module_source_relative_path($module_source_path));
        foreach ($this->shop_configuration_dao->get_all() as $shop_id => $shop_configuration) {
            $merged_module_configuration = $this->module_configuration_merging_service->merge($shop_configuration, $module_configuration)->get_module_configuration($module_configuration->get_id());
            $this->module_configuration_dao->save($merged_module_configuration, $shop_id);
            $this->class_extensions_chain_dao->save_chain($shop_id, $shop_configuration->get_class_extensions_chain());
        }
    }
    public function uninstall(string $module_source_path): void
    {
        $module_configuration = $this->metadata_module_configuration_dao->get($module_source_path);
        foreach ($this->shop_configuration_dao->get_all() as $shop_id => $shop_configuration) {
            if ($shop_configuration->has_module_configuration($module_configuration->get_id())) {
                $this->remove_module_configuration($module_configuration, $shop_id);
            }
        }
    }
    public function uninstall_by_id(string $module_id): void
    {
        foreach ($this->shop_configuration_dao->get_all() as $shop_id => $shop_configuration) {
            if ($shop_configuration->has_module_configuration($module_id)) {
                $this->remove_module_configuration($shop_configuration->get_module_configuration($module_id), $shop_id);
            }
        }
    }
    public function is_installed(string $module_source_path): bool
    {
        $module_configuration = $this->metadata_module_configuration_dao->get($module_source_path);
        return $this->shop_configuration_dao->get($this->context->get_default_shop_id())->has_module_configuration($module_configuration->get_id());
    }
    private function get_module_source_relative_path(string $module_source_path): string
    {
        return Path::make_relative($module_source_path, $this->context->get_shop_root_path());
    }
    private function remove_module_configuration(Module_Configuration $module_configuration, int $shop_id): void
    {
        $this->module_configuration_dao->delete($module_configuration->get_id(), $shop_id);
        $chain = $this->class_extensions_chain_dao->get_chain($shop_id);
        foreach ($module_configuration->get_class_extensions() as $class_extension) {
            try {
                $chain->remove_extension($class_extension);
            } catch (Extension_Not_In_Chain_Exception) {
            }
        }
        $this->class_extensions_chain_dao->save_chain($shop_id, $chain);
    }
}