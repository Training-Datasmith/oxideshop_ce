<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Core;

/**
 * Settings handler class.
 */
class Settings_Handler extends \Oxid_Esales\Eshop\Core\Base
{
    /**
     * Module type.
     *
     * e.g. 'module' or 'theme'
     *
     * @var string
     */
    protected $module_type;
    /**
     * Sets the Module type
     *
     * @param string $moduleType can be either 'module' or 'theme'
     *
     * @return self
     */
    public function set_module_type($module_type)
    {
        $this->module_type = $module_type;
        return $this;
    }
    /**
     * Get settings and module id and starts import process.
     *
     * Run module settings import logic only if it has settings array
     * On empty settings array, it will remove the settings.
     *
     * @param object $module Module or Theme Object
     */
    public function run($module): void
    {
        $module_settings = $module->get_info('settings');
        $is_theme = $this->is_theme($module->get_id());
        if (!$is_theme || $is_theme && is_array($module_settings)) {
            $this->add_module_settings($module_settings, $module->get_id());
        }
    }
    /**
     * Adds settings to database.
     *
     * @param array  $moduleSettings Module settings array
     * @param string $moduleId       Module id
     */
    protected function add_module_settings($module_settings, $module_id)
    {
        $this->remove_not_used_settings($module_settings, $module_id);
        $config = \Oxid_Esales\Eshop\Core\Registry::get_config();
        $shop_id = $config->get_shop_id();
        $module_configs = $this->get_module_configs($module_id);
        $db = \Oxid_Esales\Eshop\Core\Database_Provider::get_db();
        if (is_array($module_settings)) {
            foreach ($module_settings as $setting) {
                $oxid = \Oxid_Esales\Eshop\Core\Registry::get_utils_object()->generate_u_id();
                $module = $this->get_module_config_id($module_id);
                $name = $setting['name'];
                $type = $setting['type'];
                if ($this->is_theme($module_id)) {
                    $value = array_key_exists($name, $module_configs) ? $module_configs[$name] : $setting['value'];
                } else {
                    $value = is_null($config->get_config_param($name)) ? $setting['value'] : $config->get_config_param($name);
                }
                $group = $setting['group'];
                $constraints = '';
                if (isset($setting['constraints']) && $setting['constraints']) {
                    $constraints = $setting['constraints'];
                } elseif (isset($setting['constrains']) && $setting['constrains']) {
                    $constraints = $setting['constrains'];
                }
                $position = 1;
                if (isset($setting['position'])) {
                    $position = $setting['position'];
                }
                $config->save_shop_conf_var($type, $name, $value, $shop_id, $module);
                $delete_sql = 'DELETE FROM `oxconfigdisplay` WHERE OXCFGMODULE = :oxcfgmodule AND OXCFGVARNAME = :oxcfgvarname';
                $insert_sql = 'INSERT INTO `oxconfigdisplay` (`OXID`, `OXCFGMODULE`, `OXCFGVARNAME`, `OXGROUPING`, `OXVARCONSTRAINT`, `OXPOS`) ' . 'VALUES (:oxid, :oxcfgmodule, :oxcfgvarname, :oxgrouping, :oxvarconstraint, :oxpos)';
                $db->execute($delete_sql, ['oxcfgmodule' => $module, 'oxcfgvarname' => $name]);
                $db->execute($insert_sql, ['oxid' => $oxid, 'oxcfgmodule' => $module, 'oxcfgvarname' => $name, 'oxgrouping' => $group, 'oxvarconstraint' => $constraints, 'oxpos' => $position]);
            }
        }
    }
    /**
     * Check if module is theme.
     *
     * @param string $moduleId
     * @return bool
     */
    protected function is_theme($module_id)
    {
        $module_config_id = $this->get_module_config_id($module_id);
        $theme_type_condition = '@^' . Config::OXMODULE_THEME_PREFIX . '@i';
        return (bool) preg_match($theme_type_condition, $module_config_id);
    }
    /**
     * Removes configs which are removed from module metadata
     *
     * @param array  $moduleSettings Module settings
     * @param string $moduleId       Module id
     */
    protected function remove_not_used_settings($module_settings, $module_id)
    {
        $module_configs = array_keys($this->get_module_configs($module_id));
        $module_settings = $this->parse_module_settings($module_settings);
        $configs_to_remove = array_diff($module_configs, $module_settings);
        if (!empty($configs_to_remove)) {
            $this->remove_module_configs($module_id, $configs_to_remove);
        }
    }
    /**
     * Returns module configuration from database
     *
     * @param string $moduleId Module id
     *
     * @return array key=>value
     */
    protected function get_module_configs($module_id)
    {
        $db = Database_Provider::get_db();
        $config = \Oxid_Esales\Eshop\Core\Registry::get_config();
        $shop_id = $config->get_shop_id();
        $module = $this->get_module_config_id($module_id);
        $module_configs_query = 'SELECT oxvarname, oxvartype, oxvarvalue FROM oxconfig WHERE oxmodule = :oxmodule AND oxshopid = :oxshopid';
        $db_configs = $db->get_all($module_configs_query, ['oxmodule' => $module, 'oxshopid' => $shop_id]);
        $result = [];
        foreach ($db_configs as $one_module_config) {
            $result[$one_module_config['oxvarname']] = $config->decode_value($one_module_config['oxvartype'], $one_module_config['oxvarvalue']);
        }
        return $result;
    }
    /**
     * Parses module config variable names to array from module settings
     *
     * @param array $moduleSettings Module settings
     *
     * @return array
     */
    protected function parse_module_settings($module_settings)
    {
        $settings = [];
        if (is_array($module_settings)) {
            foreach ($module_settings as $setting) {
                $settings[] = $setting['name'];
            }
        }
        return $settings;
    }
    /**
     * Removes module configs from database
     *
     * @param string $moduleId        Module id
     * @param array  $configsToRemove Configs to remove
     */
    protected function remove_module_configs($module_id, $configs_to_remove)
    {
        $db = \Oxid_Esales\Eshop\Core\Database_Provider::get_db();
        $quoted_configs_to_remove = array_map([$db, 'quote'], $configs_to_remove);
        $delete_sql = 'DELETE
                       FROM `oxconfig`
                       WHERE oxmodule = :oxmodule AND
                             oxshopid = :oxshopid AND
                             oxvarname IN (' . implode(', ', $quoted_configs_to_remove) . ')';
        $db->execute($delete_sql, ['oxmodule' => $this->get_module_config_id($module_id), 'oxshopid' => \Oxid_Esales\Eshop\Core\Registry::get_config()->get_shop_id()]);
    }
    /**
     * Get config tables specific module id
     *
     * @param string $moduleId
     * @return string
     */
    protected function get_module_config_id($module_id)
    {
        return $this->module_type . ':' . $module_id;
    }
}