<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Framework\Module\Configuration\Service;

use Oxid_Esales\Eshop_Community\Internal\Framework\Module\Configuration\Data_Object\Module_Configuration;
use Oxid_Esales\Eshop_Community\Internal\Framework\Module\Configuration\Data_Object\Shop_Configuration;
use Oxid_Esales\Eshop_Community\Internal\Framework\Module\Setting\Setting;
class Settings_Merging_Service implements Settings_Merging_Service_Interface
{
    public function merge(Shop_Configuration $shop_configuration, Module_Configuration $module_configuration_to_merge): Module_Configuration
    {
        if ($shop_configuration->has_module_configuration($module_configuration_to_merge->get_id())) {
            $existing_module_configuration = $shop_configuration->get_module_configuration($module_configuration_to_merge->get_id());
            if (!empty($existing_module_configuration->get_module_settings()) && !empty($module_configuration_to_merge->get_module_settings())) {
                $merged_module_settings = $this->merge_module_settings($existing_module_configuration->get_module_settings(), $module_configuration_to_merge->get_module_settings());
                $module_configuration_to_merge->set_module_settings($merged_module_settings);
            }
        }
        return $module_configuration_to_merge;
    }
    /**
     * @param Setting[] $existingSettings
     * @param Setting[] $settingsToMerge
     *
     * @return Setting[]
     */
    private function merge_module_settings(array $existing_settings, array $settings_to_merge): array
    {
        foreach ($settings_to_merge as &$setting_to_merge) {
            foreach ($existing_settings as $existing_setting) {
                if ($this->should_merge($existing_setting, $setting_to_merge)) {
                    $setting_to_merge->set_value($existing_setting->get_value());
                }
            }
        }
        return $settings_to_merge;
    }
    private function should_merge(Setting $existing_setting, Setting $setting_to_merge): bool
    {
        $should_merge = $existing_setting->get_value() !== null && $existing_setting->get_name() === $setting_to_merge->get_name() && $existing_setting->get_type() === $setting_to_merge->get_type();
        if ($should_merge === true && !empty($setting_to_merge->get_constraints()) && $setting_to_merge->get_type() === 'select') {
            $result_position = array_search($existing_setting->get_value(), $setting_to_merge->get_constraints(), true);
            $should_merge = $result_position !== false;
        }
        return $should_merge;
    }
}