<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Framework\Module\Configuration\Cache;

use Oxid_Esales\Eshop_Community\Internal\Framework\Module\Configuration\Data_Object\Module_Configuration;
class Class_Property_Module_Configuration_Cache implements Module_Configuration_Cache_Interface
{
    /**
     * @var ModuleConfiguration[][]
     */
    private array $cache = [];
    public function put(int $shop_id, Module_Configuration $configuration): void
    {
        $this->cache[$shop_id][$configuration->get_id()] = $configuration;
    }
    public function get(string $module_id, int $shop_id): Module_Configuration
    {
        return $this->cache[$shop_id][$module_id];
    }
    public function exists(string $module_id, int $shop_id): bool
    {
        return isset($this->cache[$shop_id][$module_id]);
    }
    public function evict(string $module_id, int $shop_id): void
    {
        if ($this->exists($module_id, $shop_id)) {
            unset($this->cache[$shop_id][$module_id]);
        }
    }
}