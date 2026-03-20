<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Internal\Framework\Module\Cache;

interface Module_Cache_Interface
{
    public function delete_item(string $key): void;
    public function put(string $key, array $data): void;
    public function get(string $key): array;
    public function exists(string $key): bool;
}