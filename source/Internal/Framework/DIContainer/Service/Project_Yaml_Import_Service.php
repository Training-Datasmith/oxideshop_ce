<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Framework\Di_Container\Service;

use Oxid_Esales\Eshop_Community\Internal\Framework\Di_Container\Dao\Project_Yaml_Dao_Interface;
use Oxid_Esales\Eshop_Community\Internal\Framework\Di_Container\Exception\No_Service_Yaml_Exception;
use Oxid_Esales\Eshop_Community\Internal\Transition\Utility\Basic_Context_Interface;
use Symfony\Component\Filesystem\Path;
/**
 * @internal
 */
class Project_Yaml_Import_Service implements Project_Yaml_Import_Service_Interface
{
    private const SERVICE_FILE_NAME = 'services.yaml';
    public function __construct(private readonly Project_Yaml_Dao_Interface $project_yaml_dao, private readonly Basic_Context_Interface $context)
    {
    }
    public function add_import(string $service_dir): void
    {
        if (!realpath($service_dir)) {
            throw new No_Service_Yaml_Exception();
        }
        $project_config = $this->project_yaml_dao->load_project_config_file();
        $project_config->add_import($this->get_service_relative_file_path($service_dir));
        $this->project_yaml_dao->save_project_config_file($project_config);
    }
    public function remove_import(string $service_dir): void
    {
        $project_config = $this->project_yaml_dao->load_project_config_file();
        $project_config->remove_import($this->get_service_relative_file_path($service_dir));
        $this->project_yaml_dao->save_project_config_file($project_config);
    }
    /**
     * Checks if the import files exist and if not removes them
     */
    public function remove_non_existing_imports(): void
    {
        $project_config = $this->project_yaml_dao->load_project_config_file();
        $config_changed = false;
        foreach ($project_config->get_import_file_names() as $file_name) {
            if (file_exists($this->get_absolute_path($file_name))) {
                continue;
            }
            $project_config->remove_import($file_name);
            $config_changed = true;
        }
        if ($config_changed) {
            $this->project_yaml_dao->save_project_config_file($project_config);
        }
    }
    /**
     * @param $fileName
     */
    private function get_absolute_path(string $file_name): string
    {
        return Path::make_absolute($file_name, Path::get_directory($this->context->get_generated_services_file_path()));
    }
    private function get_service_relative_file_path(string $service_dir): string
    {
        return Path::make_relative($service_dir . DIRECTORY_SEPARATOR . static::SERVICE_FILE_NAME, Path::get_directory($this->context->get_generated_services_file_path()));
    }
}