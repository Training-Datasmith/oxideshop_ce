<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Internal\Framework\Session;

interface Session_Interface
{
    public function has(string $name): bool;
    public function get(string $name, mixed $default = null): mixed;
    public function set(string $name, mixed $value): void;
    public function remove(string $name): mixed;
}