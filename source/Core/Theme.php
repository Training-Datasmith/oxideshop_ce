<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Core;

/**
 * Themes handler class.
 *
 * @internal Do not make a module extension for this class.
 */
class Theme extends \Oxid_Esales\Eshop\Core\Base
{
    /**
     * Theme info array
     *
     * @var array
     */
    protected $_a_theme = [];
    /**
     * Theme info list
     *
     * @var array
     */
    protected $_a_theme_list = [];
    /**
     * Load theme info
     *
     * @param string $sOXID theme id
     *
     * @return bool
     */
    public function load($s_oxid)
    {
        $s_file_path = \Oxid_Esales\Eshop\Core\Registry::get_config()->get_views_dir() . $s_oxid . '/theme.php';
        if (file_exists($s_file_path) && is_readable($s_file_path)) {
            $a_theme = [];
            include $s_file_path;
            $this->_a_theme = $a_theme;
            $this->_a_theme['id'] = $s_oxid;
            $this->_a_theme['active'] = $this->get_active_theme_id() == $s_oxid;
            return true;
        }
        return false;
    }
    /**
     * Set theme as active
     */
    public function activate(): void
    {
        $s_error = $this->check_for_activation_errors();
        if ($s_error) {
            /** @var \OxidEsales\Eshop\Core\Exception\StandardException $oException */
            $o_exception = ox_new(\Oxid_Esales\Eshop\Core\Exception\Standard_Exception::class, $s_error);
            throw $o_exception;
        }
        $s_parent = $this->get_info('parentTheme');
        if ($s_parent) {
            \Oxid_Esales\Eshop\Core\Registry::get_config()->save_shop_conf_var('str', 'sTheme', $s_parent);
            \Oxid_Esales\Eshop\Core\Registry::get_config()->save_shop_conf_var('str', 'sCustomTheme', $this->get_id());
        } else {
            \Oxid_Esales\Eshop\Core\Registry::get_config()->save_shop_conf_var('str', 'sTheme', $this->get_id());
            \Oxid_Esales\Eshop\Core\Registry::get_config()->save_shop_conf_var('str', 'sCustomTheme', '');
        }
        $settings_handler = ox_new(\Oxid_Esales\Eshop\Core\Settings_Handler::class);
        $settings_handler->set_module_type('theme')->run($this);
    }
    /**
     * Load theme info list
     *
     * @return array
     */
    public function get_list()
    {
        $this->_a_theme_list = [];
        $s_out_dir = \Oxid_Esales\Eshop\Core\Registry::get_config()->get_views_dir();
        foreach (glob($s_out_dir . '*', GLOB_ONLYDIR) as $s_dir) {
            $o_theme = ox_new(\Oxid_Esales\Eshop\Core\Theme::class);
            if ($o_theme->load(basename($s_dir))) {
                $this->_a_theme_list[$s_dir] = $o_theme;
            }
        }
        return $this->_a_theme_list;
    }
    /**
     * Return theme information
     *
     * @param string $sName name of info item to retrieve
     *
     * @return mixed
     */
    public function get_info($s_name)
    {
        if (!isset($this->_a_theme[$s_name])) {
            return null;
        }
        return $this->_a_theme[$s_name];
    }
    /**
     * Return current active theme, or custom theme if specified
     *
     * @return string
     */
    public function get_active_theme_id()
    {
        $s_cust_theme = \Oxid_Esales\Eshop\Core\Registry::get_config()->get_config_param('sCustomTheme');
        if ($s_cust_theme) {
            return $s_cust_theme;
        }
        return \Oxid_Esales\Eshop\Core\Registry::get_config()->get_config_param('sTheme');
    }
    /**
     * Get active themes list.
     * Examples:
     *      if flow theme is active we will get ['flow']
     *      if azure is extended by some other we will get ['azure', 'extending_theme']
     *
     * @return array
     */
    public function get_active_themes_list()
    {
        $config = \Oxid_Esales\Eshop\Core\Registry::get_config();
        $active_theme_list = [];
        if (!$this->is_admin()) {
            $active_theme_list[] = $config->get_config_param('sTheme');
            if ($custom_theme_id = $config->get_config_param('sCustomTheme')) {
                $active_theme_list[] = $custom_theme_id;
            }
        }
        return $active_theme_list;
    }
    /**
     * Return loaded parent
     *
     * @return \OxidEsales\Eshop\Core\Theme
     */
    public function get_parent()
    {
        $s_parent = $this->get_info('parentTheme');
        if (!$s_parent) {
            return null;
        }
        $o_theme = ox_new(\Oxid_Esales\Eshop\Core\Theme::class);
        if ($o_theme->load($s_parent)) {
            return $o_theme;
        }
        return null;
    }
    /**
     * run pre-activation checks and return EXCEPTION_* translation string if error
     * found or false on success
     *
     * @return string
     */
    public function check_for_activation_errors()
    {
        if (!$this->get_id()) {
            return 'EXCEPTION_THEME_NOT_LOADED';
        }
        $o_parent = $this->get_parent();
        if ($o_parent) {
            $s_parent_version = $o_parent->get_info('version');
            if (!$s_parent_version) {
                return 'EXCEPTION_PARENT_VERSION_UNSPECIFIED';
            }
            $a_my_parent_versions = $this->get_info('parentVersions');
            if (!$a_my_parent_versions || !is_array($a_my_parent_versions)) {
                return 'EXCEPTION_UNSPECIFIED_PARENT_VERSIONS';
            }
            if (!in_array($s_parent_version, $a_my_parent_versions)) {
                return 'EXCEPTION_PARENT_VERSION_MISMATCH';
            }
        } elseif ($this->get_info('parentTheme')) {
            return 'EXCEPTION_PARENT_THEME_NOT_FOUND';
        }
        return false;
    }
    /**
     * Get theme ID
     *
     * @return string
     */
    public function get_id()
    {
        return $this->get_info('id');
    }
}