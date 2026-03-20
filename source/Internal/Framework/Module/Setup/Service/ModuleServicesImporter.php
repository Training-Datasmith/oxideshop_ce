<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Framework\Module\Setup\Service;

use Oxid_Esales\Eshop_Community\Internal\Framework\Di_Container\Data_Object\Di_Config_Wrapper;
use Oxid_Esales\Eshop_Community\Internal\Framework\Di_Container\Exception\No_Service_Yaml_Exception;
use Oxid_Esales\Eshop_Community\Internal\Transition\Utility\Basic_Context_Interface;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\Yaml\Yaml;
/**
 * @internal
 */
class Module_Services_Importer implements Module_Services_Importer_Interface
{
    public function __construct(private readonly Basic_Context_Interface $context)
    {
    }
    public function add_import(string $service_dir, int $shop_id): void
    {
        if (!file_exists($this->get_service_file_path($service_dir))) {
            throw new No_Service_Yaml_Exception();
        }
        $services = $this->load_di_config_file($this->context->get_active_module_services_file_path($shop_id));
        $services->add_import($this->get_service_relative_file_path($service_dir, $shop_id));
        $this->save_services_file($services, $shop_id);
    }
    public function remove_import(string $service_dir, int $shop_id): void
    {
        $services = $this->load_di_config_file($this->context->get_active_module_services_file_path($shop_id));
        $services->remove_import($this->get_service_relative_file_path($service_dir, $shop_id));
        $this->save_services_file($services, $shop_id);
    }
    private function get_service_relative_file_path(string $service_dir, int $shop_id): string
    {
        return Path::make_relative($this->get_service_file_path($service_dir), Path::get_directory($this->context->get_active_module_services_file_path($shop_id)));
    }
    private function load_di_config_file(string $path): Di_Config_Wrapper
    {
        $yaml_array = [];
        if (file_exists($path)) {
            $yaml_array = Yaml::parse(file_get_contents($path), Yaml::PARSE_CUSTOM_TAGS) ?? [];
        }
        return new Di_Config_Wrapper($yaml_array);
    }
    private function save_services_file(Di_Config_Wrapper $config, int $shop_id): void
    {
        file_put_contents($this->context->get_active_module_services_file_path($shop_id), Yaml::dump($config->get_config_as_array(), 3, 2));
    }
    private function get_service_file_path(string $service_dir): string
    {
        return Path::join($service_dir, 'services.yaml');
    }
}