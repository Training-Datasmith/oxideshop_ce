<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Framework\Config\Dao;

use Oxid_Esales\Eshop_Community\Internal\Framework\Config\Data_Object\Shop_Configuration_Setting;
use Oxid_Esales\Eshop_Community\Internal\Framework\Config\Event\Shop_Configuration_Changed_Event;
use Oxid_Esales\Eshop_Community\Internal\Framework\Config\Utility\Shop_Setting_Encoder_Interface;
use Oxid_Esales\Eshop_Community\Internal\Framework\Dao\Entry_Does_Not_Exist_Dao_Exception;
use Oxid_Esales\Eshop_Community\Internal\Framework\Database\Id;
use Oxid_Esales\Eshop_Community\Internal\Framework\Database\Query_Builder_Factory_Interface;
use Symfony\Component\Event_Dispatcher\Event_Dispatcher_Interface;
class Shop_Configuration_Setting_Dao implements Shop_Configuration_Setting_Dao_Interface
{
    public function __construct(private readonly Query_Builder_Factory_Interface $query_builder_factory, private readonly Shop_Setting_Encoder_Interface $shop_setting_encoder, private readonly Event_Dispatcher_Interface $event_dispatcher)
    {
    }
    private array $cache = [];
    public function save(Shop_Configuration_Setting $shop_configuration_setting): void
    {
        $this->delete($shop_configuration_setting);
        $query_builder = $this->query_builder_factory->create();
        $query_builder->insert('oxconfig')->values(['oxid' => ':id', 'oxshopid' => ':shopId', 'oxvarname' => ':name', 'oxvartype' => ':type', 'oxvarvalue' => ':value'])->set_parameters(['id' => Id::generate(), 'shopId' => $shop_configuration_setting->get_shop_id(), 'name' => $shop_configuration_setting->get_name(), 'type' => $shop_configuration_setting->get_type(), 'value' => $this->shop_setting_encoder->encode($shop_configuration_setting->get_type(), $shop_configuration_setting->get_value())]);
        $query_builder->execute_statement();
        $this->event_dispatcher->dispatch(new Shop_Configuration_Changed_Event($shop_configuration_setting->get_name(), $shop_configuration_setting->get_shop_id()));
    }
    /**
     * @throws EntryDoesNotExistDaoException
     */
    public function get(string $name, int $shop_id): Shop_Configuration_Setting
    {
        if (!isset($this->cache[$shop_id][$name])) {
            $query_builder = $this->query_builder_factory->create();
            $query_builder->select('oxvarvalue as value, oxvartype as type, oxvarname as name')->from('oxconfig')->where('oxshopid = :shopId')->and_where('oxvarname = :name')->and_where('oxmodule = ""')->set_parameters(['shopId' => $shop_id, 'name' => $name]);
            $result = $query_builder->fetch_associative();
            if (false === $result) {
                throw new Entry_Does_Not_Exist_Dao_Exception('Setting ' . $name . ' doesn\'t exist in the shop with id ' . $shop_id);
            }
            $setting = new Shop_Configuration_Setting();
            $setting->set_name($name)->set_value($this->shop_setting_encoder->decode($result['type'], $result['value']))->set_shop_id($shop_id)->set_type($result['type']);
            $this->cache[$shop_id][$name] = $setting;
        }
        return clone $this->cache[$shop_id][$name];
    }
    public function delete(Shop_Configuration_Setting $setting): void
    {
        $query_builder = $this->query_builder_factory->create();
        $query_builder->delete('oxconfig')->where('oxshopid = :shopId')->and_where('oxvarname = :name')->and_where('oxmodule = ""')->set_parameters(['shopId' => $setting->get_shop_id(), 'name' => $setting->get_name()]);
        $query_builder->execute_statement();
        unset($this->cache[$setting->get_shop_id()][$setting->get_name()]);
    }
}