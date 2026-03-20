<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Framework\Module\Configuration\Dao;

use Oxid_Esales\Eshop_Community\Internal\Framework\Module\Configuration\Cache\Module_Configuration_Cache_Interface;
use Oxid_Esales\Eshop_Community\Internal\Framework\Module\Configuration\Data_Mapper\Module_Configuration_Data_Mapper_Interface;
use Oxid_Esales\Eshop_Community\Internal\Framework\Module\Configuration\Data_Object\Module_Configuration;
use Oxid_Esales\Eshop_Community\Internal\Framework\Module\Configuration\Event\Module_Configuration_Changed_Event;
use Oxid_Esales\Eshop_Community\Internal\Framework\Module\Configuration\Exception\Module_Configuration_Not_Found_Exception;
use Oxid_Esales\Eshop_Community\Internal\Framework\Storage\Array_Storage_Interface;
use Oxid_Esales\Eshop_Community\Internal\Framework\Storage\File_Storage_Factory_Interface;
use Oxid_Esales\Eshop_Community\Internal\Transition\Utility\Basic_Context_Interface;
use Symfony\Component\Config\Definition\Exception\Invalid_Configuration_Exception;
use Symfony\Component\Config\Definition\Node_Interface;
use Symfony\Component\Event_Dispatcher\Event_Dispatcher_Interface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;
class Module_Configuration_Dao implements Module_Configuration_Dao_Interface
{
    public function __construct(private readonly Basic_Context_Interface $context, private readonly Module_Configuration_Data_Mapper_Interface $module_configuration_data_mapper, private readonly File_Storage_Factory_Interface $file_storage_factory, private readonly Module_Configuration_Cache_Interface $cache, private readonly Module_Configuration_Extender_Interface $module_configuration_extender, private readonly Node_Interface $node, private readonly Filesystem $filesystem, private readonly Event_Dispatcher_Interface $event_dispatcher)
    {
    }
    /**
     *
     * @throws ModuleConfigurationNotFoundException
     */
    public function get(string $module_id, int $shop_id): Module_Configuration
    {
        if (!$this->cache->exists($module_id, $shop_id)) {
            if (!file_exists($this->get_module_configuration_file_path($shop_id, $module_id))) {
                throw new Module_Configuration_Not_Found_Exception('There is no module configuration with id ' . $module_id);
            }
            $module_configuration = $this->module_configuration_data_mapper->from_data(new Module_Configuration(), $this->get_normalized_data($shop_id, $module_id));
            $module_configuration = $this->module_configuration_extender->extend($module_configuration, $shop_id);
            $this->cache->put($shop_id, $module_configuration);
        }
        return $this->cache->get($module_id, $shop_id);
    }
    public function save(Module_Configuration $module_configuration, int $shop_id): void
    {
        $this->cache->evict($module_configuration->get_id(), $shop_id);
        $this->get_storage($shop_id, $module_configuration->get_id())->save($this->module_configuration_data_mapper->to_data($module_configuration));
        $this->event_dispatcher->dispatch(new Module_Configuration_Changed_Event($module_configuration, $shop_id));
    }
    /**
     * @inheritDoc
     */
    public function get_all(int $shop_id): array
    {
        $module_configurations = [];
        foreach ($this->get_module_ids($shop_id) as $id) {
            $module_configurations[$id] = $this->get($id, $shop_id);
        }
        return $module_configurations;
    }
    /**
     * @deprecated will be completely removed
     */
    public function delete_all(int $shop_id): void
    {
        $this->filesystem->remove($this->get_modules_configuration_directory($shop_id));
    }
    public function delete(string $module_id, int $shop_id): void
    {
        $this->filesystem->remove($this->get_module_configuration_file_path($shop_id, $module_id));
    }
    public function exists(string $module_id, int $shop_id): bool
    {
        return in_array($module_id, $this->get_module_ids($shop_id), true);
    }
    private function get_storage(int $shop_id, string $module_id): Array_Storage_Interface
    {
        return $this->file_storage_factory->create($this->get_module_configuration_file_path($shop_id, $module_id));
    }
    private function get_modules_configuration_directory(int $shop_id): string
    {
        return Path::join($this->context->get_shop_configuration_directory($shop_id), 'modules');
    }
    private function get_module_ids(int $shop_id): array
    {
        $module_ids = [];
        if (file_exists($this->get_modules_configuration_directory($shop_id))) {
            $dir = new \Directory_Iterator($this->get_modules_configuration_directory($shop_id));
            foreach ($dir as $file_info) {
                if ($file_info->is_file()) {
                    $module_ids[] = $file_info->get_basename('.' . $file_info->get_extension());
                }
            }
        }
        sort($module_ids);
        return $module_ids;
    }
    private function get_module_configuration_file_path(int $shop_id, string $module_id): string
    {
        return Path::join($this->get_modules_configuration_directory($shop_id), $module_id . '.yaml');
    }
    private function get_normalized_data(int $shop_id, string $module_id): mixed
    {
        try {
            $data = $this->node->normalize($this->get_storage($shop_id, $module_id)->get());
        } catch (Invalid_Configuration_Exception $exception) {
            throw new Invalid_Configuration_Exception('File ' . $this->get_module_configuration_file_path($shop_id, $module_id) . ' is broken: ' . $exception->get_message(), $exception->get_code(), $exception);
        }
        return $data;
    }
}