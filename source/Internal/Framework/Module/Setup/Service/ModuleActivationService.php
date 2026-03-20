<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Framework\Module\Setup\Service;

use Oxid_Esales\Eshop_Community\Internal\Framework\Di_Container\Event\Project_Yaml_Changed_Event;
use Oxid_Esales\Eshop_Community\Internal\Framework\Di_Container\Exception\No_Service_Yaml_Exception;
use Oxid_Esales\Eshop_Community\Internal\Framework\Module\Configuration\Dao\Module_Configuration_Dao_Interface;
use Oxid_Esales\Eshop_Community\Internal\Framework\Module\Path\Module_Path_Resolver_Interface;
use Oxid_Esales\Eshop_Community\Internal\Framework\Module\Setup\Event\Before_Module_Deactivation_Event;
use Oxid_Esales\Eshop_Community\Internal\Framework\Module\Setup\Event\Finalizing_Module_Activation_Event;
use Oxid_Esales\Eshop_Community\Internal\Framework\Module\Setup\Event\Finalizing_Module_Deactivation_Event;
use Oxid_Esales\Eshop_Community\Internal\Framework\Module\Setup\Validator\Module_Configuration_Validator_Interface;
use Symfony\Component\Event_Dispatcher\Event_Dispatcher_Interface;
class Module_Activation_Service implements Module_Activation_Service_Interface
{
    public function __construct(private readonly Module_Configuration_Dao_Interface $module_configuration_dao, private readonly Event_Dispatcher_Interface $event_dispatcher, private readonly Module_Configuration_Validator_Interface $module_configuration_validator, private readonly Module_Services_Importer_Interface $modules_yaml_import_service, private readonly Module_Path_Resolver_Interface $module_path_resolver, private readonly Module_Configuration_Validator_Interface $deactivation_dependency_validator)
    {
    }
    public function activate(string $module_id, int $shop_id): void
    {
        $module_configuration = $this->module_configuration_dao->get($module_id, $shop_id);
        $this->module_configuration_validator->validate($module_configuration, $shop_id);
        $module_configuration->set_activated(true);
        $this->module_configuration_dao->save($module_configuration, $shop_id);
        $this->add_module_services($module_id, $shop_id);
        $this->event_dispatcher->dispatch(new Finalizing_Module_Activation_Event($shop_id, $module_id));
    }
    public function deactivate(string $module_id, int $shop_id): void
    {
        $module_configuration = $this->module_configuration_dao->get($module_id, $shop_id);
        $this->deactivation_dependency_validator->validate($module_configuration, $shop_id);
        $this->event_dispatcher->dispatch(new Before_Module_Deactivation_Event($shop_id, $module_id));
        $this->remove_module_services($module_id, $shop_id);
        $module_configuration->set_activated(false);
        $this->module_configuration_dao->save($module_configuration, $shop_id);
        $this->event_dispatcher->dispatch(new Finalizing_Module_Deactivation_Event($shop_id, $module_id));
    }
    private function add_module_services(string $module_id, int $shop_id): void
    {
        try {
            $this->modules_yaml_import_service->add_import($this->module_path_resolver->get_full_module_path_from_configuration($module_id, $shop_id), $shop_id);
            $this->event_dispatcher->dispatch(new Project_Yaml_Changed_Event());
        } catch (No_Service_Yaml_Exception) {
        }
    }
    private function remove_module_services(string $module_id, int $shop_id): void
    {
        $this->modules_yaml_import_service->remove_import($this->module_path_resolver->get_full_module_path_from_configuration($module_id, $shop_id), $shop_id);
        $this->event_dispatcher->dispatch(new Project_Yaml_Changed_Event());
    }
}