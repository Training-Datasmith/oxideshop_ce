<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Framework\Module\Setup\Validator;

use Oxid_Esales\Eshop_Community\Internal\Framework\Di_Container\Container_Builder;
use Oxid_Esales\Eshop_Community\Internal\Framework\Di_Container\Dao\Project_Yaml_Dao_Interface;
use Oxid_Esales\Eshop_Community\Internal\Framework\Di_Container\Data_Object\Di_Config_Wrapper;
use Oxid_Esales\Eshop_Community\Internal\Framework\Di_Container\Exception\No_Service_Yaml_Exception;
use Oxid_Esales\Eshop_Community\Internal\Framework\Module\Configuration\Data_Object\Module_Configuration;
use Oxid_Esales\Eshop_Community\Internal\Framework\Module\Path\Module_Path_Resolver_Interface;
use Oxid_Esales\Eshop_Community\Internal\Framework\Module\Setup\Exception\Invalid_Module_Services_Exception;
use Oxid_Esales\Eshop_Community\Internal\Transition\Utility\Context_Interface;
use Symfony\Component\Dependency_Injection\Container_Builder as SymfonyContainer;
use Symfony\Component\Filesystem\Path;
use Throwable;
class Services_Yaml_Validator implements Module_Configuration_Validator_Interface
{
    private Di_Config_Wrapper $config_file;
    private Di_Config_Wrapper $original_config_file;
    private Symfony_Container $fake_container;
    private string $module_id;
    private int $shop_id;
    public function __construct(private readonly Context_Interface $context, private readonly Project_Yaml_Dao_Interface $project_yaml_dao, private readonly Module_Path_Resolver_Interface $module_path_resolver)
    {
    }
    public function validate(Module_Configuration $configuration, int $shop_id): void
    {
        $this->backup_project_config_file();
        $this->module_id = $configuration->get_id();
        $this->shop_id = $shop_id;
        try {
            $this->import_validated_modules_services_into_project_config_file();
            $this->build_fake_container_with_modified_project_config_file();
            $this->validate_container_definitions();
        } catch (No_Service_Yaml_Exception) {
            return;
        } catch (Throwable $e) {
            throw new Invalid_Module_Services_Exception(message: "Service YAML for module [{$this->module_id}] is invalid", previous: $e);
        } finally {
            $this->restore_project_config_file();
        }
    }
    private function backup_project_config_file(): void
    {
        $this->config_file = $this->project_yaml_dao->load_project_config_file();
        $this->original_config_file = clone $this->config_file;
    }
    /**
     * We use project service file just to run validation, actual
     * module's service.yaml will be imported into active_module_services.yaml.
     * @throws NoServiceYamlException
     */
    private function import_validated_modules_services_into_project_config_file(): void
    {
        $import_file_path = Path::join($this->module_path_resolver->get_full_module_path_from_configuration($this->module_id, $this->shop_id), 'services.yaml');
        if (!realpath($import_file_path)) {
            throw new No_Service_Yaml_Exception();
        }
        $this->config_file->add_import($import_file_path);
        $this->project_yaml_dao->save_project_config_file($this->config_file);
    }
    private function build_fake_container_with_modified_project_config_file(): void
    {
        $this->fake_container = (new Container_Builder($this->context, $this->context->get_current_shop_id()))->get_container();
        foreach ($this->fake_container->get_definitions() as $definition) {
            $definition->set_public(true);
        }
        $this->fake_container->compile(true);
    }
    private function validate_container_definitions(): void
    {
        foreach ($this->fake_container->get_definitions() as $definition_key => $definition) {
            $this->fake_container->get($definition_key);
        }
    }
    private function restore_project_config_file(): void
    {
        $this->project_yaml_dao->save_project_config_file($this->original_config_file);
    }
}