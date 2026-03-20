<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Controller\Admin;

use Ox_Admin_Details;
use Oxid_Esales\Eshop\Core\Registry;
class Theme_Configuration extends \Oxid_Esales\Eshop\Application\Controller\Admin\Shop_Configuration
{
    protected $_s_theme;
    /** @inheritdoc */
    public function render()
    {
        $my_config = \Oxid_Esales\Eshop\Core\Registry::get_config();
        $s_theme = $this->_s_theme = $this->get_edit_object_id();
        $s_shop_id = $my_config->get_shop_id();
        if (!isset($s_theme)) {
            $s_theme = $this->_s_theme = \Oxid_Esales\Eshop\Core\Registry::get_config()->get_config_param('sTheme');
        }
        $o_theme = ox_new(\Oxid_Esales\Eshop\Core\Theme::class);
        if ($o_theme->load($s_theme)) {
            $this->_a_view_data['oTheme'] = $o_theme;
            try {
                $a_db_variables = $this->load_conf_vars($s_shop_id, $this->get_module_for_config_vars());
                $this->_a_view_data['var_constraints'] = $a_db_variables['constraints'];
                $this->_a_view_data['var_grouping'] = $a_db_variables['grouping'];
                foreach ($this->_a_conf_params as $s_type => $s_param) {
                    $this->_a_view_data[$s_param] = $a_db_variables['vars'][$s_type] ?? null;
                }
            } catch (\Oxid_Esales\Eshop\Core\Exception\Standard_Exception $exception) {
                Registry::get_utils_view()->add_error_to_display($exception);
                Registry::get_logger()->error($exception->get_message(), [$exception]);
            }
        } else {
            \Oxid_Esales\Eshop\Core\Registry::get_utils_view()->add_error_to_display(ox_new(\Oxid_Esales\Eshop\Core\Exception\Standard_Exception::class, 'EXCEPTION_THEME_NOT_LOADED'));
        }
        return 'theme_config';
    }
    /**
     * return theme filter for config variables
     *
     * @return string
     */
    protected function get_module_for_config_vars()
    {
        if ($this->_s_theme === null) {
            $this->_s_theme = $this->get_edit_object_id();
        }
        return \Oxid_Esales\Eshop\Core\Config::OXMODULE_THEME_PREFIX . $this->_s_theme;
    }
    /**
     * Saves shop configuration variables
     */
    public function save_conf_vars(): void
    {
        $my_config = \Oxid_Esales\Eshop\Core\Registry::get_config();
        Ox_Admin_Details::save();
        $s_shop_id = $my_config->get_shop_id();
        $s_module = $this->get_module_for_config_vars();
        foreach ($this->_a_conf_params as $s_type => $s_param) {
            $a_conf_vars = Registry::get_request()->get_request_escaped_parameter($s_param);
            if (is_array($a_conf_vars)) {
                foreach ($a_conf_vars as $s_name => $s_value) {
                    $my_config->save_shop_conf_var($s_type, $s_name, $this->serialize_conf_var($s_type, $s_name, $s_value), $s_shop_id, $s_module);
                }
            }
        }
    }
}