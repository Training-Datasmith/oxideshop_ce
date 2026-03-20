<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Framework\Module\Configuration\Dao;

use Oxid_Esales\Eshop_Community\Internal\Framework\Module\Configuration\Data_Object\Module_Dependencies;
use Oxid_Esales\Eshop_Community\Internal\Framework\Module\Path\Module_Path_Resolver_Interface;
use Oxid_Esales\Eshop_Community\Internal\Framework\Storage\Array_Storage_Interface;
use Oxid_Esales\Eshop_Community\Internal\Framework\Storage\File_Storage_Factory_Interface;
use Oxid_Esales\Eshop_Community\Internal\Transition\Utility\Context_Interface;
use Symfony\Component\Filesystem\Path;
class Module_Dependency_Dao implements Module_Dependency_Dao_Interface
{
    public function __construct(private readonly File_Storage_Factory_Interface $file_storage_factory, private readonly Module_Path_Resolver_Interface $module_path_resolver, private readonly Context_Interface $context)
    {
    }
    public function get(string $module_id): Module_Dependencies
    {
        return new Module_Dependencies($this->storage_exists($module_id) ? $this->get_storage($module_id)->get() : []);
    }
    private function storage_exists(string $module_id): bool
    {
        return file_exists($this->get_storage_file_path($module_id));
    }
    private function get_storage(string $module_id): Array_Storage_Interface
    {
        return $this->file_storage_factory->create($this->get_storage_file_path($module_id));
    }
    private function get_storage_file_path(string $module_id): string
    {
        $module_path = $this->module_path_resolver->get_full_module_path_from_configuration($module_id, $this->context->get_current_shop_id());
        return Path::join($module_path, 'dependencies.yaml');
    }
}