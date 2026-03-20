<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Framework\Module\Configuration\Dao;

use Oxid_Esales\Eshop_Community\Internal\Framework\Env\Env_Url_Formatter;
use Oxid_Esales\Eshop_Community\Internal\Framework\Storage\File_Storage_Factory_Interface;
use Oxid_Esales\Eshop_Community\Internal\Transition\Utility\Basic_Context_Interface;
use Symfony\Component\Config\Definition\Exception\Invalid_Configuration_Exception;
use Symfony\Component\Config\Definition\Node_Interface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;
class Module_Environment_Configuration_Dao implements Module_Environment_Configuration_Dao_Interface
{
    public function __construct(private readonly File_Storage_Factory_Interface $file_storage_factory, private readonly Filesystem $file_system, private readonly Node_Interface $node, private readonly Basic_Context_Interface $context)
    {
    }
    public function get(string $module_id, int $shop_id): array
    {
        $data = [];
        $configuration_file_path = $this->get_environment_configuration_file_path($module_id, $shop_id);
        if ($this->file_system->exists($configuration_file_path)) {
            $storage = $this->file_storage_factory->create($this->get_environment_configuration_file_path($module_id, $shop_id));
            try {
                $data = $this->node->normalize($storage->get());
            } catch (Invalid_Configuration_Exception $exception) {
                throw new Invalid_Configuration_Exception('File ' . $this->get_environment_configuration_file_path($module_id, $shop_id) . ' is broken: ' . $exception->get_message());
            }
        }
        return $data;
    }
    public function remove(string $module_id, int $shop_id): void
    {
        $path = $this->get_environment_configuration_file_path($module_id, $shop_id);
        if ($this->file_system->exists($path)) {
            $this->file_system->rename($path, $path . '.bak', true);
        }
    }
    private function get_environment_configuration_file_path(string $module_id, int $shop_id): string
    {
        return Path::join(Env_Url_Formatter::to_env_url($this->context->get_project_configuration_directory()), 'shops', (string) $shop_id, 'modules', $module_id . '.yaml');
    }
}