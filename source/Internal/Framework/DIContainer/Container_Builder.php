<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Framework\Di_Container;

use Oxid_Esales\Eshop_Community\Internal\Framework\Di_Container\Compiler_Pass\Route_Pass;
use Oxid_Esales\Eshop_Community\Internal\Framework\Di_Container\Compiler_Pass\View_Controller_Pass;
use Oxid_Esales\Eshop_Community\Internal\Framework\Edition\Edition;
use Oxid_Esales\Eshop_Community\Internal\Framework\Env\Env_Url_Formatter;
use Oxid_Esales\Eshop_Community\Internal\Framework\Logger\Logger_Service_Factory;
use Oxid_Esales\Eshop_Community\Internal\Transition\Utility\Basic_Context_Interface;
use Oxid_Esales\Eshop_Community\Internal\Transition\Utility\Context;
use Symfony\Component\Config\Exception\File_Locator_File_Not_Found_Exception;
use Symfony\Component\Config\Exception\Loader_Load_Exception;
use Symfony\Component\Config\File_Locator;
use Symfony\Component\Console\Dependency_Injection\Add_Console_Command_Pass;
use Symfony\Component\Dependency_Injection\Container_Builder as SymfonyContainerBuilder;
use Symfony\Component\Dependency_Injection\Loader\Yaml_File_Loader;
use Symfony\Component\Event_Dispatcher\Dependency_Injection\Register_Listeners_Pass;
use Symfony\Component\Filesystem\Path;
/**
 * @internal
 */
class Container_Builder
{
    private Symfony_Container_Builder $container_builder;
    public function __construct(private readonly Basic_Context_Interface $basic_context, private readonly int $shop_id = 1)
    {
    }
    public function get_container(): Symfony_Container_Builder
    {
        $this->container_builder = new Symfony_Container_Builder();
        $this->container_builder->set_parameter('oxid_esales.current_shop_id', $this->shop_id);
        $this->container_builder->set_parameter('oxid_esales.shop_source_directory', $this->basic_context->get_source_path());
        $this->container_builder->add_compiler_pass(new Register_Listeners_Pass());
        $this->container_builder->add_compiler_pass(new Add_Console_Command_Pass());
        $this->container_builder->add_compiler_pass(new View_Controller_Pass());
        $this->container_builder->add_compiler_pass(new Route_Pass());
        $this->load_edition_services();
        $this->load_component_services();
        $this->load_module_services();
        $this->load_project_services();
        $this->load_project_subshop_services();
        $this->load_environment_services();
        $this->load_subshop_environment_services();
        return $this->container_builder;
    }
    private function load_edition_services(): void
    {
        foreach ($this->get_editions_root_paths() as $edition_path) {
            $this->get_yaml_loader([$edition_path])->load('Internal/services.yaml');
        }
    }
    private function get_editions_root_paths(): array
    {
        return match ($this->basic_context->get_edition()) {
            Edition::Community => [$this->basic_context->get_edition_source_path(Edition::Community)],
            Edition::Professional => [$this->basic_context->get_edition_source_path(Edition::Community), $this->basic_context->get_edition_source_path(Edition::Professional)],
            Edition::Enterprise => [$this->basic_context->get_edition_source_path(Edition::Community), $this->basic_context->get_edition_source_path(Edition::Professional), $this->basic_context->get_edition_source_path(Edition::Enterprise)],
        };
    }
    private function load_component_services(): void
    {
        $this->load_yaml_if_exists($this->get_yaml_loader([]), $this->basic_context->get_generated_services_file_path());
    }
    private function load_module_services(): void
    {
        $module_services_file_path = $this->basic_context->get_active_module_services_file_path($this->shop_id);
        try {
            $this->load_yaml_if_exists($this->get_yaml_loader([]), $module_services_file_path);
        } catch (Loader_Load_Exception $exception) {
            (new Logger_Service_Factory(new Context($this->shop_id)))->get_logger()->error("Can't load module services file path {$module_services_file_path}. " . 'Please check if all imports in the file are correct.', [$exception]);
        }
    }
    private function load_project_services(): void
    {
        $this->load_project_extension_files($this->basic_context->get_project_configuration_directory());
    }
    private function load_project_subshop_services(): void
    {
        $this->load_project_extension_files($this->basic_context->get_shop_configuration_directory($this->shop_id));
    }
    private function load_subshop_environment_services(): void
    {
        $this->load_project_extension_files($this->get_shop_configuration_path_for_specific_environment());
    }
    private function get_shop_configuration_path_for_specific_environment(): string
    {
        return Path::join(Env_Url_Formatter::to_env_url($this->basic_context->get_project_configuration_directory()), Path::make_relative($this->basic_context->get_shop_configuration_directory($this->shop_id), $this->basic_context->get_project_configuration_directory()));
    }
    private function load_environment_services(): void
    {
        $this->load_project_extension_files(Env_Url_Formatter::to_env_url($this->basic_context->get_project_configuration_directory()));
    }
    private function load_project_extension_files(string $configuration_url): void
    {
        foreach (['services.yaml', 'parameters.yaml'] as $file) {
            $this->load_yaml_if_exists($this->get_yaml_loader([]), Path::join($configuration_url, $file));
        }
    }
    private function get_yaml_loader(array $paths): Yaml_File_Loader
    {
        return new Yaml_File_Loader($this->container_builder, new File_Locator($paths));
    }
    private function load_yaml_if_exists(Yaml_File_Loader $loader, string $yaml_file): void
    {
        try {
            $loader->load($yaml_file);
        } catch (File_Locator_File_Not_Found_Exception) {
        }
    }
}