<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Eshop_Community\Internal\Framework\Module\Meta_Data\Data_Mapper;

use Oxid_Esales\Eshop_Community\Internal\Framework\Module\Configuration\Data_Object\Module_Configuration;
use Oxid_Esales\Eshop_Community\Internal\Framework\Module\Configuration\Data_Object\Module_Configuration\Class_Extension;
use Oxid_Esales\Eshop_Community\Internal\Framework\Module\Configuration\Data_Object\Module_Configuration\Controller;
use Oxid_Esales\Eshop_Community\Internal\Framework\Module\Configuration\Data_Object\Module_Configuration\Event;
use Oxid_Esales\Eshop_Community\Internal\Framework\Module\Meta_Data\Dao\Meta_Data_Provider;
use Oxid_Esales\Eshop_Community\Internal\Framework\Module\Meta_Data\Validator\Meta_Data_Schema_Validator_Interface;
use Oxid_Esales\Eshop_Community\Internal\Framework\Module\Setting\Setting;
class Meta_Data_Mapper implements Meta_Data_To_Module_Configuration_Data_Mapper_Interface
{
    public function __construct(private readonly Meta_Data_Schema_Validator_Interface $validator)
    {
    }
    public function from_data(array $meta_data): Module_Configuration
    {
        $this->validate_parameter_format($meta_data);
        $this->validator->validate($meta_data[Meta_Data_Provider::METADATA_FILEPATH], $meta_data[Meta_Data_Provider::METADATA_METADATA_VERSION], $meta_data[Meta_Data_Provider::METADATA_MODULE_DATA]);
        $module_data = $meta_data[Meta_Data_Provider::METADATA_MODULE_DATA];
        $module_configuration = new Module_Configuration();
        $module_configuration->set_id($module_data[Meta_Data_Provider::METADATA_ID])->set_version($module_data[Meta_Data_Provider::METADATA_VERSION] ?? '')->set_description($module_data[Meta_Data_Provider::METADATA_DESCRIPTION] ?? [])->set_lang($module_data[Meta_Data_Provider::METADATA_LANG] ?? '')->set_thumbnail($module_data[Meta_Data_Provider::METADATA_THUMBNAIL] ?? '')->set_author($module_data[Meta_Data_Provider::METADATA_AUTHOR] ?? '')->set_url($module_data[Meta_Data_Provider::METADATA_URL] ?? '')->set_email($module_data[Meta_Data_Provider::METADATA_EMAIL] ?? '');
        if (isset($module_data[Meta_Data_Provider::METADATA_TITLE])) {
            $module_configuration->set_title($module_data[Meta_Data_Provider::METADATA_TITLE]);
        }
        return $this->map_module_configuration_settings($module_configuration, $meta_data);
    }
    private function map_module_configuration_settings(Module_Configuration $module_configuration, array $meta_data): Module_Configuration
    {
        $module_data = $meta_data[Meta_Data_Provider::METADATA_MODULE_DATA];
        if (isset($module_data[Meta_Data_Provider::METADATA_EXTEND])) {
            foreach ($module_data[Meta_Data_Provider::METADATA_EXTEND] as $shop_class => $module_class) {
                $module_configuration->add_class_extension(new Class_Extension($shop_class, $module_class));
            }
        }
        if (isset($module_data[Meta_Data_Provider::METADATA_CONTROLLERS])) {
            foreach ($module_data[Meta_Data_Provider::METADATA_CONTROLLERS] as $id => $controller_class_name_space) {
                $module_configuration->add_controller(new Controller($id, $controller_class_name_space));
            }
        }
        if (isset($module_data[Meta_Data_Provider::METADATA_EVENTS])) {
            foreach ($module_data[Meta_Data_Provider::METADATA_EVENTS] as $action => $method) {
                $module_configuration->add_event(new Event($action, $method));
            }
        }
        return $this->map_settings($module_configuration, $module_data);
    }
    private function validate_parameter_format(array $data): void
    {
        $mandatory_keys = [Meta_Data_Provider::METADATA_METADATA_VERSION, Meta_Data_Provider::METADATA_MODULE_DATA];
        foreach ($mandatory_keys as $mandatory_key) {
            if (false === \array_key_exists($mandatory_key, $data)) {
                throw new \InvalidArgumentException('The key "' . $mandatory_key . '" must be present in the array passed in the parameter');
            }
        }
    }
    /**
     * @param $moduleData
     */
    private function map_settings(Module_Configuration $module_configuration, array $module_data): Module_Configuration
    {
        if (isset($module_data[Meta_Data_Provider::METADATA_SETTINGS])) {
            foreach ($module_data[Meta_Data_Provider::METADATA_SETTINGS] as $data) {
                $setting = new Setting();
                $setting->set_name($data['name']);
                $setting->set_type($data['type']);
                if (isset($data['group'])) {
                    $setting->set_group_name($data['group']);
                }
                if (isset($data['value'])) {
                    $setting->set_value($data['value']);
                }
                if (isset($data['constraints'])) {
                    $setting->set_constraints($data['constraints']);
                }
                if (isset($data['position'])) {
                    $setting->set_position_in_group((int) $data['position']);
                }
                $module_configuration->add_module_setting($setting);
            }
        }
        return $module_configuration;
    }
}