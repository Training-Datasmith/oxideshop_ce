<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Framework\Module\Cache;

use Psr\Cache\Cache_Item_Pool_Interface;
class Module_Cache implements Module_Cache_Interface
{
    public function __construct(private readonly Cache_Item_Pool_Interface $cache)
    {
    }
    public function delete_item(string $key): void
    {
        $this->cache->delete_item($key);
    }
    public function put(string $key, array $data): void
    {
        $cache_item = $this->cache->get_item($key);
        $cache_item->set($data);
        $this->cache->save($cache_item);
    }
    /**
     * @throws CacheNotFoundException
     */
    public function get(string $key): array
    {
        $cache_item = $this->cache->get_item($key);
        if (!$cache_item->is_hit()) {
            throw new Cache_Not_Found_Exception("Cache with key '{$key}' not found.");
        }
        return $cache_item->get();
    }
    public function exists(string $key): bool
    {
        return $this->cache->get_item($key)->is_hit();
    }
}