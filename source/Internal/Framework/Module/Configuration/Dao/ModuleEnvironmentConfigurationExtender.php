<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Framework\Module\Configuration\Dao;

use Oxid_Esales\Eshop_Community\Internal\Framework\Module\Configuration\Data_Object\Module_Configuration;
use Oxid_Esales\Eshop_Community\Internal\Framework\Module\Setting\Setting;
use Symfony\Component\Event_Dispatcher\Event_Dispatcher_Interface;
class Module_Environment_Configuration_Extender implements Module_Configuration_Extender_Interface
{
    public function __construct(private readonly Module_Environment_Configuration_Dao_Interface $module_environment_configuration_dao, private readonly Event_Dispatcher_Interface $event_dispatcher)
    {
    }
    public function extend(Module_Configuration $module_configuration, int $shop_id): Module_Configuration
    {
        $environment_data = $this->module_environment_configuration_dao->get($module_configuration->get_id(), $shop_id);
        if (isset($environment_data['moduleSettings'])) {
            foreach ($environment_data['moduleSettings'] as $setting_id => $environment_setting) {
                if (!$module_configuration->has_module_setting($setting_id)) {
                    $this->process_orphan_setting($shop_id, $module_configuration->get_id(), $setting_id);
                    continue;
                }
                $this->merge_environment_setting($module_configuration->get_module_setting($setting_id), $environment_setting);
            }
        }
        return $module_configuration;
    }
    public function merge_environment_setting(Setting $original_setting, array $environment_setting): void
    {
        if (isset($environment_setting['value'])) {
            $original_setting->set_value($environment_setting['value']);
        }
        if (isset($environment_setting['group'])) {
            $original_setting->set_group_name($environment_setting['group']);
        }
        if (isset($environment_setting['position'])) {
            $original_setting->set_position_in_group($environment_setting['position']);
        }
        if (isset($environment_setting['constraints'])) {
            $original_setting->set_constraints($environment_setting['constraints']);
        }
    }
    private function process_orphan_setting(int $shop_id, string $module_id, string $orphan_setting_id): void
    {
        $this->event_dispatcher->dispatch(new Shop_Environment_With_Orphan_Setting_Event($shop_id, $module_id, $orphan_setting_id));
    }
}