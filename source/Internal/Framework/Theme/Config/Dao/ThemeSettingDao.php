<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Framework\Theme\Config\Dao;

use Oxid_Esales\Eshop_Community\Internal\Framework\Config\Utility\Shop_Setting_Encoder_Interface;
use Oxid_Esales\Eshop_Community\Internal\Framework\Dao\Entry_Does_Not_Exist_Dao_Exception;
use Oxid_Esales\Eshop_Community\Internal\Framework\Database\Id;
use Oxid_Esales\Eshop_Community\Internal\Framework\Database\Query_Builder_Factory_Interface;
use Oxid_Esales\Eshop_Community\Internal\Framework\Theme\Config\Data_Object\Theme_Setting;
use Oxid_Esales\Eshop_Community\Internal\Framework\Theme\Event\Theme_Setting_Changed_Event;
use Symfony\Component\Event_Dispatcher\Event_Dispatcher_Interface;
class Theme_Setting_Dao implements Theme_Setting_Dao_Interface
{
    private const THEME_MODULE_PREFIX = 'theme:';
    public function __construct(private readonly Query_Builder_Factory_Interface $query_builder_factory, private readonly Shop_Setting_Encoder_Interface $shop_setting_encoder, private readonly Event_Dispatcher_Interface $event_dispatcher)
    {
    }
    private array $cache = [];
    public function save(Theme_Setting $setting): void
    {
        $this->delete($setting);
        $module_identifier = $this->get_module_identifier($setting->get_theme_id());
        $query_builder = $this->query_builder_factory->create();
        $query_builder->insert('oxconfig')->values(['oxid' => ':id', 'oxshopid' => ':shopId', 'oxmodule' => ':module', 'oxvarname' => ':name', 'oxvartype' => ':type', 'oxvarvalue' => ':value'])->set_parameters(['id' => Id::generate(), 'shopId' => $setting->get_shop_id(), 'module' => $module_identifier, 'name' => $setting->get_name(), 'type' => $setting->get_type(), 'value' => $this->shop_setting_encoder->encode($setting->get_type(), $setting->get_value())]);
        $query_builder->execute_statement();
        $this->event_dispatcher->dispatch(new Theme_Setting_Changed_Event($setting->get_name(), $setting->get_shop_id(), $module_identifier));
    }
    public function get(string $name, int $shop_id, string $theme_id): Theme_Setting
    {
        $module_identifier = $this->get_module_identifier($theme_id);
        if (!isset($this->cache[$shop_id][$theme_id][$name])) {
            $query_builder = $this->query_builder_factory->create();
            $query_builder->select('oxvarvalue as value, oxvartype as type, oxvarname as name')->from('oxconfig')->where('oxshopid = :shopId')->and_where('oxvarname = :name')->and_where('oxmodule = :module')->set_parameters(['shopId' => $shop_id, 'name' => $name, 'module' => $module_identifier]);
            $result = $query_builder->fetch_associative();
            if ($result === false) {
                throw new Entry_Does_Not_Exist_Dao_Exception('Setting ' . $name . ' for theme ' . $theme_id . ' does not exist in the shop with id ' . $shop_id);
            }
            $setting = new Theme_Setting();
            $setting->set_theme_id($theme_id)->set_name($name)->set_shop_id($shop_id)->set_type($result['type'])->set_value($this->shop_setting_encoder->decode($result['type'], $result['value']));
            $this->cache[$shop_id][$theme_id][$name] = $setting;
        }
        return clone $this->cache[$shop_id][$theme_id][$name];
    }
    public function delete(Theme_Setting $setting): void
    {
        $query_builder = $this->query_builder_factory->create();
        $query_builder->delete('oxconfig')->where('oxshopid = :shopId')->and_where('oxvarname = :name')->and_where('oxmodule = :module')->set_parameters(['shopId' => $setting->get_shop_id(), 'name' => $setting->get_name(), 'module' => $this->get_module_identifier($setting->get_theme_id())]);
        $query_builder->execute_statement();
        unset($this->cache[$setting->get_shop_id()][$setting->get_theme_id()][$setting->get_name()]);
    }
    private function get_module_identifier(string $theme_id): string
    {
        return self::THEME_MODULE_PREFIX . $theme_id;
    }
}