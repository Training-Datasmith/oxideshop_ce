<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Framework\Di_Container\Dao;

use function array_key_exists;
use Oxid_Esales\Eshop_Community\Internal\Framework\Di_Container\Event\Project_Yaml_Changed_Event;
use Oxid_Esales\Eshop_Community\Internal\Transition\Utility\Basic_Context_Interface;
use Symfony\Component\Event_Dispatcher\Event_Dispatcher_Interface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\Yaml\Yaml;
use Unit_Enum;
readonly class Parameter_Dao implements Parameter_Dao_Interface
{
    public function __construct(private Basic_Context_Interface $context, private Filesystem $filesystem, private Event_Dispatcher_Interface $event_dispatcher)
    {
    }
    public function add(string $name, Unit_Enum|float|int|bool|array|string|null $value, int $shop_id): void
    {
        $this->save_parameter_into_file($name, $value, $this->get_shop_parameter_file_path($shop_id));
    }
    public function remove(string $name, int $shop_id): void
    {
        $this->remove_parameter_from_file($name, $this->get_shop_parameter_file_path($shop_id));
    }
    public function has(string $name, int $shop_id): bool
    {
        return array_key_exists($name, $this->get_parameters($this->get_shop_parameter_file_path($shop_id)));
    }
    private function get_parameters(string $file_path): array
    {
        if (file_exists($file_path)) {
            return Yaml::parse(file_get_contents($file_path), Yaml::PARSE_CUSTOM_TAGS)['parameters'] ?? [];
        }
        return [];
    }
    private function save_parameters(array $parameters, string $file_path): void
    {
        $this->filesystem->dump_file($file_path, Yaml::dump(['parameters' => $parameters], 3, 2));
        $this->event_dispatcher->dispatch(new Project_Yaml_Changed_Event());
    }
    private function save_parameter_into_file(string $name, mixed $value, string $file_path): void
    {
        $parameters = $this->get_parameters($file_path);
        $parameters[$name] = $value;
        $this->save_parameters($parameters, $file_path);
    }
    private function remove_parameter_from_file(string $name, string $file_path): void
    {
        $parameters = $this->get_parameters($file_path);
        unset($parameters[$name]);
        $this->save_parameters($parameters, $file_path);
    }
    private function get_shop_parameter_file_path(int $shop_id): string
    {
        return Path::join($this->context->get_shop_configuration_directory($shop_id), 'parameters.yaml');
    }
}