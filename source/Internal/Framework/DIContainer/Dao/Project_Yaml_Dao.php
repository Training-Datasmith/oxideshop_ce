<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Framework\Di_Container\Dao;

use Oxid_Esales\Eshop_Community\Internal\Framework\Di_Container\Data_Object\Di_Config_Wrapper;
use Oxid_Esales\Eshop_Community\Internal\Transition\Utility\Basic_Context_Interface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\Yaml\Yaml;
class Project_Yaml_Dao implements Project_Yaml_Dao_Interface
{
    public function __construct(private readonly Basic_Context_Interface $context, private readonly Filesystem $filesystem)
    {
    }
    public function load_project_config_file(): Di_Config_Wrapper
    {
        return $this->load_di_config_file($this->context->get_generated_services_file_path());
    }
    public function save_project_config_file(Di_Config_Wrapper $config): void
    {
        $config = $this->convert_absolute_paths_to_relative($config);
        if (!$this->filesystem->exists($this->get_generated_services_file_directory())) {
            $this->filesystem->mkdir($this->get_generated_services_file_directory());
        }
        file_put_contents($this->context->get_generated_services_file_path(), Yaml::dump($config->get_config_as_array(), 3, 2));
    }
    public function load_di_config_file(string $path): Di_Config_Wrapper
    {
        $yaml_array = [];
        if (file_exists($path)) {
            $yaml_array = Yaml::parse(file_get_contents($path), Yaml::PARSE_CUSTOM_TAGS) ?? [];
        }
        return new Di_Config_Wrapper($yaml_array);
    }
    private function get_generated_services_file_directory(): string
    {
        return \dirname($this->context->get_generated_services_file_path());
    }
    private function convert_absolute_paths_to_relative(Di_Config_Wrapper $config_wrapper): Di_Config_Wrapper
    {
        foreach ($config_wrapper->get_import_file_names() as $file_name) {
            if (Path::is_absolute($file_name)) {
                $relative_path = Path::make_relative($file_name, Path::get_directory($this->context->get_generated_services_file_path()));
                $config_wrapper->add_import($relative_path);
                $config_wrapper->remove_import($file_name);
            }
        }
        return $config_wrapper;
    }
}