<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Framework\Module\Facade;

use Oxid_Esales\Eshop_Community\Internal\Framework\Module\Cache\Module_Cache_Interface;
use Oxid_Esales\Eshop_Community\Internal\Framework\Module\Configuration\Dao\Module_Configuration_Dao_Interface;
use Oxid_Esales\Eshop_Community\Internal\Framework\Module\Configuration\Exception\Module_Setting_Not_Fount_Exception;
use Oxid_Esales\Eshop_Community\Internal\Framework\Module\Setting\Event\Setting_Changed_Event;
use Oxid_Esales\Eshop_Community\Internal\Transition\Utility\Context_Interface;
use Symfony\Component\Event_Dispatcher\Event_Dispatcher_Interface;
use Symfony\Component\String\Unicode_String;
class Module_Setting_Service implements Module_Setting_Service_Interface
{
    public function __construct(private readonly Context_Interface $context, private readonly Module_Configuration_Dao_Interface $module_configuration_dao, private readonly Module_Cache_Interface $module_cache, private readonly Event_Dispatcher_Interface $event_dispatcher)
    {
    }
    public function get_integer(string $name, string $module_id): int
    {
        return $this->get_value($module_id, $name);
    }
    public function get_float(string $name, string $module_id): float
    {
        return $this->get_value($module_id, $name);
    }
    public function get_string(string $name, string $module_id): Unicode_String
    {
        return new Unicode_String($this->get_value($module_id, $name));
    }
    public function get_boolean(string $name, string $module_id): bool
    {
        return $this->get_value($module_id, $name);
    }
    public function get_collection(string $name, string $module_id): array
    {
        return $this->get_value($module_id, $name);
    }
    public function save_integer(string $name, int $value, string $module_id): void
    {
        $this->save_setting_to_module_configuration($module_id, $name, $value);
    }
    public function save_float(string $name, float $value, string $module_id): void
    {
        $this->save_setting_to_module_configuration($module_id, $name, $value);
    }
    public function save_string(string $name, string $value, string $module_id): void
    {
        $this->save_setting_to_module_configuration($module_id, $name, $value);
    }
    public function save_boolean(string $name, bool $value, string $module_id): void
    {
        $this->save_setting_to_module_configuration($module_id, $name, $value);
    }
    public function save_collection(string $name, array $value, string $module_id): void
    {
        $this->save_setting_to_module_configuration($module_id, $name, $value);
    }
    public function exists(string $name, string $module_id): bool
    {
        try {
            $this->get_value($module_id, $name);
        } catch (Module_Setting_Not_Fount_Exception) {
            return false;
        }
        return true;
    }
    private function save_setting_to_module_configuration(string $module_id, string $name, mixed $value): void
    {
        $shop_id = $this->context->get_current_shop_id();
        $module_configuration = $this->module_configuration_dao->get($module_id, $shop_id);
        $setting = $module_configuration->get_module_setting($name);
        $setting->set_value($value);
        $this->module_configuration_dao->save($module_configuration, $shop_id);
        $this->event_dispatcher->dispatch(new Setting_Changed_Event($name, $shop_id, $module_id));
    }
    private function get_value(string $module_id, string $name): mixed
    {
        $shop_id = $this->context->get_current_shop_id();
        $cache_key = $this->get_cache_key($module_id, $name);
        if (!$this->module_cache->exists($cache_key)) {
            $this->module_cache->put($cache_key, ['value' => $this->module_configuration_dao->get($module_id, $shop_id)->get_module_setting($name)->get_value()]);
        }
        return $this->module_cache->get($cache_key, $shop_id)['value'];
    }
    private function get_cache_key(string $module_id, string $name): string
    {
        return $module_id . '-setting-' . $name;
    }
}