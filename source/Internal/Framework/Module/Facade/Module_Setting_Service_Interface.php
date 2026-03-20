<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Framework\Module\Facade;

use Symfony\Component\String\Unicode_String;
interface Module_Setting_Service_Interface
{
    public function get_integer(string $name, string $module_id): int;
    public function get_float(string $name, string $module_id): float;
    public function get_string(string $name, string $module_id): Unicode_String;
    public function get_boolean(string $name, string $module_id): bool;
    public function get_collection(string $name, string $module_id): array;
    public function save_integer(string $name, int $value, string $module_id): void;
    public function save_float(string $name, float $value, string $module_id): void;
    public function save_string(string $name, string $value, string $module_id): void;
    public function save_boolean(string $name, bool $value, string $module_id): void;
    public function save_collection(string $name, array $value, string $module_id): void;
    public function exists(string $name, string $module_id): bool;
}