<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Framework\Module\Configuration\Data_Mapper\Module_Configuration;

use Oxid_Esales\Eshop_Community\Internal\Framework\Module\Configuration\Data_Mapper\Module_Configuration_Data_Mapper_Interface;
use Oxid_Esales\Eshop_Community\Internal\Framework\Module\Configuration\Data_Object\Module_Configuration;
use Oxid_Esales\Eshop_Community\Internal\Framework\Module\Setting\Setting;
class Module_Settings_Data_Mapper implements Module_Configuration_Data_Mapper_Interface
{
    public const MAPPING_KEY = 'moduleSettings';
    public function to_data(Module_Configuration $configuration): array
    {
        $data = [];
        if ($configuration->has_module_settings()) {
            $data[self::MAPPING_KEY] = $this->map_settings_to_data($configuration);
        }
        return $data;
    }
    public function from_data(Module_Configuration $module_configuration, array $data): Module_Configuration
    {
        if (isset($data[self::MAPPING_KEY])) {
            $this->map_settings_from_data($module_configuration, $data);
        }
        return $module_configuration;
    }
    private function map_settings_to_data(Module_Configuration $configuration): array
    {
        $data = [];
        foreach ($configuration->get_module_settings() as $setting) {
            if ($setting->get_group_name()) {
                $data[$setting->get_name()]['group'] = $setting->get_group_name();
            }
            if ($setting->get_type()) {
                $data[$setting->get_name()]['type'] = $setting->get_type();
            }
            $data[$setting->get_name()]['value'] = $setting->get_value();
            if (!empty($setting->get_constraints())) {
                $data[$setting->get_name()]['constraints'] = $setting->get_constraints();
            }
            if ($setting->get_position_in_group() > 0) {
                $data[$setting->get_name()]['position'] = $setting->get_position_in_group();
            }
        }
        return $data;
    }
    private function map_settings_from_data(Module_Configuration $configuration, array $data): Module_Configuration
    {
        if (isset($data[self::MAPPING_KEY])) {
            foreach ($data[self::MAPPING_KEY] as $name => $setting_data) {
                $setting = new Setting();
                $setting->set_name($name);
                $setting->set_type($setting_data['type']);
                if (isset($setting_data['value'])) {
                    $setting->set_value($setting_data['value']);
                }
                if (!isset($setting_data['value'])) {
                    $setting->set_value('');
                }
                if (isset($setting_data['group'])) {
                    $setting->set_group_name($setting_data['group']);
                }
                if (isset($setting_data['position'])) {
                    $setting->set_position_in_group($setting_data['position']);
                }
                if (isset($setting_data['constraints'])) {
                    $setting->set_constraints($setting_data['constraints']);
                }
                $configuration->add_module_setting($setting);
            }
        }
        return $configuration;
    }
}