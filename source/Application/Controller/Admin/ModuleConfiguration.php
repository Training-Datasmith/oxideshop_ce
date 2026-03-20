<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Controller\Admin;

use Oxid_Esales\Eshop\Core\Module\Module;
use Oxid_Esales\Eshop\Core\Registry;
use Oxid_Esales\Eshop\Core\Str;
use Oxid_Esales\Eshop_Community\Core\Di\Container_Facade;
use Oxid_Esales\Eshop_Community\Internal\Framework\Module\Configuration\Bridge\Module_Configuration_Dao_Bridge_Interface;
use Oxid_Esales\Eshop_Community\Internal\Framework\Module\Setting\Event\Setting_Changed_Event;
use Oxid_Esales\Eshop_Community\Internal\Framework\Module\Setting\Setting;
#[\Allow_Dynamic_Properties]
class Module_Configuration extends \Oxid_Esales\Eshop\Application\Controller\Admin\Shop_Configuration
{
    /**
     * Add additional config type for modules.
     */
    public function __construct()
    {
        parent::__construct();
        $this->_a_conf_params['password'] = 'confpassword';
    }
    /** @inheritdoc */
    public function render()
    {
        $this->_s_module_id = $this->get_selected_module_id();
        $module_id = $this->_s_module_id;
        try {
            $module_configuration = Container_Facade::get(Module_Configuration_Dao_Bridge_Interface::class)->get($module_id);
            if (!empty($module_configuration->get_module_settings())) {
                $format_module_settings = $this->format_module_settings_for_template($module_configuration->get_module_settings());
                $this->_a_view_data['var_constraints'] = $format_module_settings['constraints'];
                $this->_a_view_data['var_grouping'] = $format_module_settings['grouping'];
                foreach ($this->_a_conf_params as $s_type => $s_param) {
                    $this->_a_view_data[$s_param] = $format_module_settings['vars'][$s_type] ?? null;
                }
            }
        } catch (\Throwable $throwable) {
            Registry::get_utils_view()->add_error_to_display($throwable);
            Registry::get_logger()->error($throwable->get_message());
        }
        $module = ox_new(Module::class);
        $module->load($module_id);
        $this->_a_view_data['oModule'] = $module;
        return 'module_config';
    }
    /**
     * Saves shop configuration variables
     */
    public function save_conf_vars(): void
    {
        $this->reset_content_cache();
        $this->_s_module_id = $this->get_selected_module_id();
        try {
            $this->save_module_config_variables($this->_s_module_id, $this->get_config_variables_from_request());
        } catch (\Throwable $throwable) {
            Registry::get_utils_view()->add_error_to_display($throwable);
            Registry::get_logger()->error($throwable->get_message());
        }
    }
    private function get_selected_module_id(): string
    {
        $module_id = $this->_s_edit_object_id ?? Registry::get_request()->get_request_escaped_parameter('oxid') ?? Registry::get_session()->get_variable('saved_oxid');
        if ($module_id === null) {
            throw new \InvalidArgumentException('Module id not found.');
        }
        return $module_id;
    }
    private function save_module_config_variables(string $module_id, array $variables): void
    {
        $module_configuration_dao_bridge = Container_Facade::get(Module_Configuration_Dao_Bridge_Interface::class);
        $module_configuration = $module_configuration_dao_bridge->get($module_id);
        foreach ($variables as $name => $value) {
            if ($module_configuration->has_module_setting($name)) {
                $module_setting = $module_configuration->get_module_setting($name);
                if ($module_setting->get_type() === 'aarr') {
                    $value = $this->multiline_to_aarray($value);
                }
                if ($module_setting->get_type() === 'arr') {
                    $value = $this->multiline_to_array($value);
                }
                if ($module_setting->get_type() === 'bool') {
                    $value = filter_var($value, FILTER_VALIDATE_BOOLEAN);
                }
                if ($module_setting->get_type() === 'num' && filter_var($value, FILTER_VALIDATE_INT) !== false) {
                    $value = (int) $value;
                } elseif ($module_setting->get_type() === 'num' && filter_var($value, FILTER_VALIDATE_FLOAT) !== false) {
                    $value = (float) $value;
                }
                $module_setting->set_value($value);
                Container_Facade::dispatch(new Setting_Changed_Event($name, (int) Registry::get_config()->get_shop_id(), $module_id));
            }
        }
        $module_configuration_dao_bridge->save($module_configuration);
    }
    private function get_config_variables_from_request(): array
    {
        $settings = [];
        foreach ($this->_a_conf_params as $request_parameter_key) {
            $settings_from_request = Registry::get_request()->get_request_escaped_parameter($request_parameter_key);
            if (\is_array($settings_from_request)) {
                foreach ($settings_from_request as $name => $value) {
                    $settings[$name] = $value;
                }
            }
        }
        return $settings;
    }
    /**
     * @param Setting[] $moduleSettings
     */
    private function format_module_settings_for_template(array $module_settings): array
    {
        $conf_vars = ['bool' => [], 'str' => [], 'arr' => [], 'aarr' => [], 'select' => [], 'password' => []];
        $constraints = [];
        $grouping = [];
        foreach ($module_settings as $setting) {
            $name = $setting->get_name();
            $value_type = $setting->get_type();
            $value = null;
            if ($setting->get_value() !== null) {
                $value = match ($setting->get_type()) {
                    'arr' => $this->array_to_multiline($setting->get_value()),
                    'aarr' => $this->aarray_to_multiline($setting->get_value()),
                    'bool' => filter_var($setting->get_value(), FILTER_VALIDATE_BOOLEAN),
                    default => $setting->get_value(),
                };
                $value = Str::get_str()->htmlentities($value);
            }
            $group = $setting->get_group_name();
            $conf_vars[$value_type][$name] = $value;
            $constraints[$name] = $setting->get_constraints() ?? '';
            if ($group) {
                if (!isset($grouping[$group])) {
                    $grouping[$group] = [$name => $value_type];
                } else {
                    $grouping[$group][$name] = $value_type;
                }
            }
        }
        return ['vars' => $conf_vars, 'constraints' => $constraints, 'grouping' => $grouping];
    }
}