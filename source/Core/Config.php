<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Core;

use Oxid_Esales\Eshop\Application\Controller\Frontend_Controller;
use Oxid_Esales\Eshop\Application\Controller\Oxid_Start_Controller;
use Oxid_Esales\Eshop\Application\Model\Shop;
use Oxid_Esales\Eshop\Core\Registry;
use Oxid_Esales\Eshop_Community\Core\Di\Container_Facade;
use Oxid_Esales\Eshop_Community\Internal\Framework\Config\Event\Shop_Configuration_Changed_Event;
use Oxid_Esales\Eshop_Community\Internal\Framework\Edition\Edition;
use Oxid_Esales\Eshop_Community\Internal\Framework\Theme\Bridge\Admin_Theme_Bridge_Interface;
use Oxid_Esales\Eshop_Community\Internal\Framework\Theme\Event\Theme_Setting_Changed_Event;
use Oxid_Esales\Eshop_Community\Internal\Transition\Utility\Basic_Context_Interface;
use stdClass;
use Symfony\Component\Filesystem\Path;
//max integer
define('MAX_64BIT_INTEGER', '18446744073709551615');
/**
 * Main shop configuration class.
 */
#[\Allow_Dynamic_Properties]
class Config extends \Oxid_Esales\Eshop\Core\Base
{
    /**
     * Application starter instance
     *
     * @var OxidStartController
     */
    private $_o_start;
    /**
     * Active shop object.
     *
     * @var object
     */
    protected $_o_act_shop;
    /**
     * Active Views object array. Object has setters/getters for these properties:
     *   _sClass - name of current view class
     *   _sFnc   - name of current action function
     *
     * @var array
     */
    protected $_a_active_views = [];
    /**
     * Array of global parameters.
     *
     * @var array
     */
    protected $_a_global_params = [];
    /**
     * Shop config parameters storage array
     *
     * @var array
     */
    protected $_a_config_params = [];
    /**
     * Theme config parameters storage array
     *
     * @var array
     */
    protected $_a_theme_config_params = [];
    /**
     * Current language Id
     *
     * @var int
     */
    protected $_i_language_id;
    /**
     * Current shop Id
     *
     * @var int
     */
    protected $_i_shop_id;
    /**
     * Out dir name
     *
     * @var string
     */
    protected $_s_out_dir = 'out';
    /**
     * Image dir name
     *
     * @var string
     */
    protected $_s_image_dir = 'img';
    /**
     * Dyn Image dir name
     *
     * @var string
     */
    protected $_s_picture_dir = 'pictures';
    /**
     * Master pictures dir name
     *
     * @var string
     */
    protected $_s_master_picture_dir = 'master';
    /**
     * Template dir name
     *
     * @var string
     */
    protected $_s_template_dir = 'tpl';
    /**
     * Resource dir name
     *
     * @var string
     */
    protected $_s_resource_dir = 'src';
    /**
     * Modules dir name
     *
     * @var string
     */
    protected $_s_modules_dir = 'modules';
    /**
     * Whether shop is in SSL mode
     *
     * @var bool
     */
    protected $_bl_is_ssl;
    /**
     * Absolute image dirs for each shops
     *
     * @var array
     */
    protected $_a_abs_dyn_image_dir = [];
    /**
     * Active currency object
     *
     * @var array
     */
    protected $_o_act_currency_object;
    /**
     * Indicates if Config::init() method has been already run.
     * Is checked for loading config variables on demand.
     *
     * @var bool
     */
    protected $_bl_init = false;
    private bool $init_vars = false;
    /**
     * prefix for oxModule field for themes in oxConfig and oxConfigDisplay tables
     *
     * @var string
     */
    public const OXMODULE_THEME_PREFIX = 'theme:';
    /**
     * Returns config parameter value if such parameter exists
     *
     * @param string $name    config parameter name
     * @param mixed  $default default value if no config var is found default null
     *
     * @return mixed
     */
    public function get_config_param($name, $default = null)
    {
        $this->init_vars($this->get_shop_id());
        if (isset($this->_a_config_params[$name])) {
            $value = $this->_a_config_params[$name];
        } elseif (isset($this->{$name})) {
            $value = $this->{$name};
        } else {
            $value = $default;
        }
        return $value;
    }
    /**
     * Stores config parameter value in config
     *
     * @param string $name  config parameter name
     * @param mixed  $value config parameter value
     */
    public function set_config_param($name, $value): void
    {
        if (isset($this->_a_config_params[$name])) {
            $this->_a_config_params[$name] = $value;
        } elseif (isset($this->{$name})) {
            $this->{$name} = $value;
        } else {
            $this->_a_config_params[$name] = $value;
        }
    }
    /**
     * Parse SEO url parameters.
     */
    protected function process_seo_call()
    {
        // TODO: refactor shop bootstrap and parse url params as soon as possible
        if (is_search_engine_url()) {
            ox_new(\Oxid_Esales\Eshop\Core\Seo_Decoder::class)->process_seo_call();
        }
    }
    /**
     * Initialize configuration variables
     *
     * @throws \OxidEsales\Eshop\Core\Exception\DatabaseException
     * @param int $shopId
     */
    public function init_vars($shop_id): void
    {
        if ($this->init_vars === true) {
            return;
        }
        $this->init_vars = true;
        $this->set_defaults();
        $config_loaded = $this->load_vars_from_db($shop_id);
        // loading shop config
        if (empty($shop_id) || !$config_loaded) {
            // if no config values where loaded (some problems with DB), throwing an exception
            $exception = new \Oxid_Esales\Eshop\Core\Exception\Database_Exception('Unable to load shop config values from database', 0, new \Exception());
            throw $exception;
        }
        // loading theme config options
        $this->load_vars_from_db($shop_id, null, Config::OXMODULE_THEME_PREFIX . $this->get_config_param('sTheme'));
        // checking if custom theme (which has defined parent theme) config options should be loaded over parent theme (#3362)
        if ($this->get_config_param('sCustomTheme')) {
            $this->load_vars_from_db($shop_id, null, Config::OXMODULE_THEME_PREFIX . $this->get_config_param('sCustomTheme'));
        }
        $this->load_additional_configuration();
        // Admin handling
        $this->set_config_param('blAdmin', is_admin());
        if (defined('OX_ADMIN_DIR')) {
            $this->set_config_param('sAdminDir', OX_ADMIN_DIR);
        }
    }
    /**
     * Starts session manager
     */
    public function init(): void
    {
        // Duplicated init protection
        if ($this->_bl_init) {
            return;
        }
        $this->_bl_init = true;
        $this->init_vars = false;
        try {
            // config params initialization
            $this->init_vars($this->get_shop_id());
            // application initialization
            $this->initialize_shop();
            $this->_o_start = ox_new(\Oxid_Esales\Eshop\Application\Controller\Oxid_Start_Controller::class);
            $this->_o_start->app_init();
        } catch (\Oxid_Esales\Eshop\Core\Exception\Database_Exception $exception) {
            $this->handle_db_connection_exception($exception);
        } catch (\Oxid_Esales\Eshop\Core\Exception\Cookie_Exception $exception) {
            $this->handle_cookie_exception($exception);
        }
    }
    /**
     * Reloads all configuration.
     */
    public function reinitialize(): void
    {
        $this->_bl_init = false;
        $this->init_vars = false;
        $this->init();
    }
    /**
     * Load any additional configuration on Config::init.
     */
    protected function load_additional_configuration()
    {
    }
    /**
     * Initializes main shop tasks - processing of SEO calls, starting of session.
     */
    protected function initialize_shop()
    {
        $this->process_seo_call();
        $session = \Oxid_Esales\Eshop\Core\Registry::get_session();
        $session->start();
    }
    /**
     * Set important defaults.
     */
    protected function set_defaults()
    {
        if (is_null($this->get_config_param('sDefaultLang'))) {
            $this->set_config_param('sDefaultLang', 0);
        }
        if (is_null($this->get_config_param('blCheckTemplates'))) {
            $this->set_config_param('blCheckTemplates', false);
        }
        if (is_null($this->get_config_param('blAllowArticlesubclass'))) {
            $this->set_config_param('blAllowArticlesubclass', false);
        }
        if (is_null($this->get_config_param('iAdminListSize'))) {
            $this->set_config_param('iAdminListSize', 9);
        }
        if (is_null($this->get_config_param('iZoomPicCount'))) {
            $this->set_config_param('iZoomPicCount', 4);
        }
        $this->set_config_param('sCoreDir', __DIR__ . DIRECTORY_SEPARATOR);
    }
    /**
     * Load config values from DB
     *
     * @param int    $shopId   shop ID to load parameters
     * @param array  $onlyVars array of params to load (optional)
     * @param string $module   module vars to load, empty for base options
     *
     * @return bool
     */
    protected function load_vars_from_db($shop_id, $only_vars = null, $module = '')
    {
        $db = \Oxid_Esales\Eshop\Core\Database_Provider::get_db();
        $params = ['oxshopid' => $shop_id, 'oxmodule' => $module];
        $select = '
            SELECT oxvarname, oxvartype, oxvarvalue
            FROM oxconfig
            WHERE oxshopid = :oxshopid AND oxmodule LIKE :oxmodule
        ';
        $select .= $this->get_config_params_select_snippet($only_vars);
        $result = $db->get_all($select, $params);
        foreach ($result as $value) {
            $var_name = $value['oxvarname'];
            $var_type = $value['oxvartype'];
            $var_val = $value['oxvarvalue'];
            $this->set_conf_var_from_db($var_name, $var_type, $var_val);
            //setting theme options array
            if ($module) {
                $this->_a_theme_config_params[$var_name] = $module;
            }
        }
        return (bool) count($result);
    }
    /**
     * Allow loading from some vars only from baseshop
     *
     * @param array $vars
     *
     * @return string
     */
    protected function get_config_params_select_snippet($vars)
    {
        $select = '';
        if (is_array($vars) && !empty($vars)) {
            foreach ($vars as &$field) {
                $field = '"' . $field . '"';
            }
            $select = ' and oxvarname in ( ' . implode(', ', $vars) . ' ) ';
        }
        return $select;
    }
    /**
     * Sets config variable to config object, first unserializing it by given type.
     *
     * @param string $varName variable name
     * @param string $varType variable type - arr, aarr, bool or str
     * @param string $varVal  serialized by type value
     */
    protected function set_conf_var_from_db($var_name, $var_type, $var_val)
    {
        match ($var_type) {
            'arr', 'aarr' => $this->set_config_param($var_name, unserialize($var_val, ['allowed_classes' => false])),
            'bool' => $this->set_config_param($var_name, $var_val == 'true' || $var_val == '1'),
            default => $this->set_config_param($var_name, $var_val),
        };
    }
    /**
     * Unsets all session data.
     */
    public function page_close()
    {
        if ($this->has_active_views_chain()) {
            // do not commit session until active views chain exists
            return;
        }
        return $this->_o_start->page_close();
    }
    /**
     * Get request 'cl' parameter which is the controller id.
     *
     * @return string|null
     */
    public function get_request_controller_id()
    {
        return Registry::get_request()->get_request_escaped_parameter('cl');
    }
    /**
     * Use this function to get the controller class hidden behind the request's 'cl' parameter.
     *
     * @return mixed
     */
    public function get_request_controller_class()
    {
        $controller_id = $this->get_request_controller_id();
        return Registry::get_controller_class_name_resolver()->get_class_name_by_id($controller_id);
    }
    /**
     * Returns uploaded file parameter
     *
     * @param string $paramName param name
     */
    public function get_uploaded_file($param_name)
    {
        return $_FILES[$param_name];
    }
    /**
     * Sets global parameter value
     *
     * @param string $name  name of parameter
     * @param mixed  $value value to store
     */
    public function set_global_parameter($name, $value): void
    {
        $this->_a_global_params[$name] = $value;
    }
    /**
     * Returns global parameter value
     *
     * @param string $name name of cached parameter
     *
     * @return mixed
     */
    public function get_global_parameter($name)
    {
        return $this->_a_global_params[$name] ?? null;
    }
    /**
     * Checks if passed parameter has special chars and replaces them.
     * Returns checked value.
     *
     * @param mixed $value value to process escaping
     * @param array $raw   keys of unescaped values
     *
     * @return mixed
     */
    public function check_param_special_chars(&$value, $raw = null)
    {
        return Registry::get(\Oxid_Esales\Eshop\Core\Request::class)->check_param_special_chars($value, $raw);
    }
    /**
     * Active Shop id setter
     *
     * @param int    $shopId shop id
     */
    public function set_shop_id($shop_id): void
    {
        $session = \Oxid_Esales\Eshop\Core\Registry::get_session();
        $session->set_variable('actshop', $shop_id);
        $this->_i_shop_id = $shop_id;
    }
    /**
     * Returns active shop ID.
     *
     * @deprecated
     * @see ContextInterface::getCurrentShopId()
     *
     * @return int
     */
    public function get_shop_id()
    {
        if (is_null($this->_i_shop_id)) {
            $shop_id = $this->calculate_active_shop_id();
            $this->set_shop_id($shop_id);
            if (!$this->is_valid_shop_id($shop_id)) {
                $shop_id = $this->get_base_shop_id();
            }
            $this->set_shop_id($shop_id);
        }
        return $this->_i_shop_id;
    }
    /**
     * Set is shop url
     *
     * @param bool $isSsl - state bool value
     */
    public function set_is_ssl($is_ssl = false): void
    {
        $this->_bl_is_ssl = $is_ssl;
    }
    /**
     * Checks if WEB session is SSL.
     */
    protected function check_ssl()
    {
        $my_utils_server = Registry::get_utils_server();
        $server_vars = $my_utils_server->get_server_var();
        $https_server_var = $my_utils_server->get_server_var('HTTPS');
        $this->set_is_ssl();
        if ($https_server_var === 'on' || $https_server_var === 'ON' || $https_server_var == '1') {
            $this->set_is_ssl(Container_Facade::get_parameter('oxid_esales.shop_url') || $this->get_config_param('sMallSSLShopURL'));
            if (!$this->_bl_is_ssl && $this->is_admin()) {
                $this->set_is_ssl(Container_Facade::get_parameter('oxid_esales.shop_admin_url') !== null);
            }
        }
        //additional special handling for profihost customers
        if (isset($server_vars['HTTP_X_FORWARDED_SERVER']) && (str_contains($server_vars['HTTP_X_FORWARDED_SERVER'], 'ssl') || str_contains($server_vars['HTTP_X_FORWARDED_SERVER'], 'secure-online-shopping.de'))) {
            $this->set_is_ssl(true);
        }
    }
    /**
     * Checks if WEB session is SSL. Returns true if yes.
     *
     * @return bool
     */
    public function is_ssl()
    {
        if (is_null($this->_bl_is_ssl)) {
            $this->check_ssl();
        }
        return $this->_bl_is_ssl;
    }
    /**
     * Checks if shop runs in https only mode
     * https only mode means there is no http url but only a https url
     *
     * @return bool
     */
    public function is_https_only()
    {
        return $this->is_ssl();
    }
    /**
     * Compares current URL to supplied string
     *
     * @param string $url URL
     *
     * @return bool true if $url is equal to current page URL
     */
    public function is_current_url($url)
    {
        /** @var UtilsServer $utilsServer */
        $utils_server = Registry::get_utils_server();
        return $utils_server->is_current_url($url);
    }
    /**
     * Compares current protocol to supplied url string
     *
     * @param string $url URL
     *
     * @return bool true if $url is equal to current page URL
     */
    public function is_current_protocol($url)
    {
        // Missing protocol, cannot proceed, assuming true.
        if (!$url || !str_starts_with($url, 'http')) {
            return true;
        }
        return str_starts_with($url, 'https:') == $this->is_ssl();
    }
    /**
     * Returns config sShopURL or sMallShopURL if secondary shop
     *
     * @param int  $lang  language
     * @param bool $admin if set true, function returns shop url without checking language/subshops for different url.
     *
     * @return string
     */
    public function get_shop_url($lang = null, $admin = null)
    {
        $url = null;
        $admin ??= $this->is_admin();
        if (!$admin) {
            $url = $this->get_shop_url_by_language($lang);
            if (!$url) {
                $url = $this->get_mall_shop_url();
            }
        }
        if (!$url) {
            return Container_Facade::get_parameter('oxid_esales.shop_url');
        }
        return $url;
    }
    /**
     * Returns utils dir URL
     *
     * @return string
     */
    public function get_core_utils_url()
    {
        return $this->get_current_shop_url() . 'Core/utils/';
    }
    /**
     * Returns SSL or non SSL shop URL without index.php depending on Mall
     * affecting environment is admin mode and current ssl usage status
     *
     * @param bool $admin if admin
     *
     * @return string
     */
    public function get_current_shop_url($admin = null)
    {
        if ($admin === null) {
            $admin = $this->is_admin();
        }
        if ($admin) {
            $url = Container_Facade::get_parameter('oxid_esales.shop_admin_url');
            if (!$url) {
                return $this->get_shop_url() . $this->get_config_param('sAdminDir') . '/';
            }
            return $url;
        }
        return $this->get_shop_url();
    }
    /**
     * Returns SSL or not SSL shop URL with index.php and sid
     *
     * @param int $lang language (optional)
     *
     * @return string
     */
    public function get_shop_current_url($lang = null)
    {
        return Registry::get_utils_url()->process_url($this->get_shop_url($lang) . 'index.php', false);
    }
    /**
     * Returns shop non SSL URL including index.php and sid.
     *
     * @param int  $lang  language
     * @param bool $admin if admin
     *
     * @return string
     */
    public function get_shop_home_url($lang = null, $admin = null)
    {
        return Registry::get_utils_url()->process_url($this->get_shop_url($lang, $admin) . 'index.php', false);
    }
    /**
     * Returns widget start non SSL URL including widget.php and sid.
     *
     * @param int   $languageId    language
     * @param bool  $inAdmin       if admin
     * @param array $urlParameters parameters which should be added to URL.
     *
     * @return string
     */
    public function get_widget_url($language_id = null, $in_admin = null, $url_parameters = [])
    {
        $utils_url = Registry::get_utils_url();
        $widget_url = $this->get_shop_url($language_id, $in_admin);
        $widget_url = $utils_url->process_url($widget_url . 'widget.php', false);
        if (!isset($language_id)) {
            $language = Registry::get_lang();
            $language_id = $language->get_base_language();
        }
        $url_lang = $utils_url->get_url_language_parameter($language_id);
        $widget_url = $utils_url->append_url($widget_url, $url_lang, true);
        return $utils_url->append_url($widget_url, $url_parameters, true, true);
    }
    /**
     * Returns shop SSL URL with index.php and sid.
     *
     * @return string
     */
    public function get_shop_secure_home_url()
    {
        return Registry::get_utils_url()->process_url($this->get_shop_url() . 'index.php', false);
    }
    /**
     * Returns active shop currency.
     *
     * @return string
     */
    public function get_shop_currency()
    {
        if (null === $curr = Registry::get_request()->get_request_escaped_parameter('cur')) {
            if (null === $curr = Registry::get_request()->get_request_escaped_parameter('currency')) {
                $session = \Oxid_Esales\Eshop\Core\Registry::get_session();
                $curr = $session->get_variable('currency');
            }
        }
        return (int) $curr;
    }
    /**
     * Returns active shop currency object.
     *
     * @return stdClass
     */
    public function get_act_shop_currency_object()
    {
        if ($this->_o_act_currency_object === null) {
            $cur = $this->get_shop_currency();
            $currencies = $this->get_currency_array();
            if (!isset($currencies[$cur])) {
                $this->_o_act_currency_object = reset($currencies);
                // reset() returns the first element
            } else {
                $this->_o_act_currency_object = $currencies[$cur];
            }
        }
        return $this->_o_act_currency_object;
    }
    /**
     * Sets the actual currency
     *
     * @param int $cur 0 = EUR, 1 = GBP, 2 = CHF
     */
    public function set_act_shop_currency($cur): void
    {
        $currencies = $this->get_currency_array();
        if (isset($currencies[$cur])) {
            $session = \Oxid_Esales\Eshop\Core\Registry::get_session();
            $session->set_variable('currency', $cur);
            $this->_o_act_currency_object = null;
        }
    }
    /**
     * Returns path to out dir
     *
     * @param bool $absolute mode - absolute/relative path
     *
     * @return string
     */
    public function get_out_dir($absolute = true)
    {
        if ($absolute) {
            return Path::join(Container_Facade::get_parameter('oxid_esales.shop_source_directory'), $this->_s_out_dir) . DIRECTORY_SEPARATOR;
        }
        return $this->_s_out_dir . DIRECTORY_SEPARATOR;
    }
    /**
     * Returns path to out dir
     *
     * @param bool $absolute mode - absolute/relative path
     *
     * @return string
     */
    public function get_views_dir($absolute = true)
    {
        return Path::join($this->get_app_dir($absolute), 'views') . DIRECTORY_SEPARATOR;
    }
    /**
     * Returns path to translations dir
     *
     * @param string $file     File name
     * @param string $dir      Directory name
     * @param bool   $absolute mode - absolute/relative path
     *
     * @return string
     */
    public function get_translations_dir($file, $dir, $absolute = true)
    {
        $path = Path::join($this->get_app_dir($absolute), 'translations', $dir, $file);
        return is_readable($path) ? $path : false;
    }
    /**
     * Returns path to out dir
     *
     * @param bool $absolute mode - absolute/relative path
     *
     * @return string
     */
    public function get_app_dir($absolute = true)
    {
        if ($absolute) {
            return Path::join(Container_Facade::get_parameter('oxid_esales.shop_source_directory'), 'Application') . DIRECTORY_SEPARATOR;
        }
        return 'Application' . DIRECTORY_SEPARATOR;
    }
    /**
     * Returns url to out dir
     *
     * @param bool $ssl       Whether to force ssl
     * @param bool $admin     Whether to force admin
     * @param bool $nativeImg Whether to force native image dirs
     *
     * @return string
     */
    public function get_out_url($ssl = null, $admin = null, $native_img = false)
    {
        $admin = is_null($admin) ? $this->is_admin() : $admin;
        if ($native_img && !$admin) {
            $url = $this->get_shop_url();
        } else {
            $url = Container_Facade::get_parameter('oxid_esales.shop_url');
            if (!$url && $admin) {
                $url = Container_Facade::get_parameter('oxid_esales.shop_admin_url') . '../';
            }
        }
        return $url . $this->_s_out_dir . '/';
    }
    /**
     * Finds and returns files or folders path in out dir
     *
     * @param string $file       File name
     * @param string $dir        Directory name
     * @param bool   $admin      Whether to force admin
     * @param int    $lang       Language id
     * @param int    $shop       Shop id
     * @param string $theme      Theme name
     * @param bool   $absolute   mode - absolute/relative path
     * @param bool   $ignoreCust Ignore custom theme
     *
     * @return string
     */
    public function get_dir($file, $dir, $admin, $lang = null, $shop = null, $theme = null, $absolute = true, $ignore_cust = false)
    {
        if (is_null($theme)) {
            $theme = $this->get_config_param('sTheme');
        }
        if ($admin) {
            $theme = Container_Facade::get(Admin_Theme_Bridge_Interface::class)->get_active_theme();
        }
        if ($dir != $this->_s_template_dir) {
            $base = $this->get_out_dir($absolute);
            $abs_base = $this->get_out_dir();
        } else {
            $base = $this->get_views_dir($absolute);
            $abs_base = $this->get_views_dir();
        }
        $lang_abbr = '-';
        // false means skip language folder check
        if ($lang !== false) {
            $language = Registry::get_lang();
            if (is_null($lang)) {
                $lang = $language->get_edit_language();
            }
            $lang_abbr = $language->get_language_abbr($lang);
        }
        if (is_null($shop)) {
            $shop = $this->get_shop_id();
        }
        //Load from
        $path = "{$theme}/{$shop}/{$lang_abbr}/{$dir}/{$file}";
        $cache_key = $path . "_{$ignore_cust}{$absolute}";
        if (($return = Registry::get_utils()->from_static_cache($cache_key)) !== null) {
            return $return;
        }
        $return = $this->get_edition_template("{$theme}/{$dir}/{$file}");
        // Check for custom template
        $custom_theme = $this->get_config_param('sCustomTheme');
        if (!$return && !$admin && !$ignore_cust && $custom_theme && $custom_theme != $theme) {
            $return = $this->get_dir($file, $dir, $admin, $lang, $shop, $custom_theme, $absolute, $ignore_cust);
        }
        //test lang level ..
        if (!$return && !$admin && is_readable($abs_base . $path)) {
            $return = $base . $path;
        }
        //test shop level ..
        if (!$return && !$admin) {
            $return = $this->get_shop_level_dir($base, $abs_base, $file, $dir, $admin, $lang, $shop, $theme, $absolute, $ignore_cust);
        }
        //test theme language level ..
        $path = "{$theme}/{$lang_abbr}/{$dir}/{$file}";
        if (!$return && $lang !== false && is_readable($abs_base . $path)) {
            $return = $base . $path;
        }
        //test theme level ..
        $path = "{$theme}/{$dir}/{$file}";
        if (!$return && is_readable($abs_base . $path)) {
            $return = $base . $path;
        }
        //test out language level ..
        $path = "{$lang_abbr}/{$dir}/{$file}";
        if (!$return && $lang !== false && is_readable($abs_base . $path)) {
            $return = $base . $path;
        }
        //test out level ..
        $path = "{$dir}/{$file}";
        if (!$return && is_readable($abs_base . $path)) {
            $return = $base . $path;
        }
        // TODO: implement logic to log missing paths
        Registry::get_utils()->to_static_cache($cache_key, $return);
        return $return;
    }
    /**
     * @param string $base
     * @param string $absBase
     * @param string $file
     * @param string $dir
     * @param bool   $admin
     * @param int    $lang
     * @param int    $shop
     * @param string $theme
     * @param bool   $absolute
     * @param bool   $ignoreCust
     *
     * @return bool|string
     */
    protected function get_shop_level_dir($base, $abs_base, $file, $dir, $admin, $lang, $shop, $theme, $absolute, $ignore_cust)
    {
        $return = false;
        $path = "{$theme}/{$shop}/{$dir}/{$file}";
        if (is_readable($abs_base . $path)) {
            return $base . $path;
        }
        return $return;
    }
    /**
     * Finds and returns file or folder url in out dir
     *
     * @param string $file      File name
     * @param string $dir       Directory name
     * @param bool   $admin     Whether to force admin
     * @param bool   $ssl       Whether to force ssl
     * @param bool   $nativeImg Whether to force native image dirs
     * @param int    $lang      Language id
     * @param int    $shop      Shop id
     * @param string $theme     Theme name
     *
     * @return string
     */
    public function get_url($file, $dir, $admin = null, $ssl = null, $native_img = false, $lang = null, $shop = null, $theme = null)
    {
        return str_replace($this->get_out_dir(), $this->get_out_url($ssl, $admin, $native_img), $this->get_dir($file, $dir, $admin, $lang, $shop, $theme));
    }
    /**
     * Finds and returns image files or folders path
     *
     * @param string $file  File name
     * @param bool   $admin Whether to force admin
     *
     * @return string
     */
    public function get_image_path($file, $admin = false)
    {
        return $this->get_dir($file, $this->_s_image_dir, $admin);
    }
    /**
     * Finds and returns image folder url
     *
     * @param bool   $admin     Whether to force admin
     * @param bool   $ssl       Whether to force ssl
     * @param bool   $nativeImg Whether to force native image dirs
     * @param string $file      Image file name
     *
     * @return string
     */
    public function get_image_url($admin = false, $ssl = null, $native_img = null, $file = null)
    {
        $native_img = is_null($native_img) ? $this->get_config_param('blNativeImages') : $native_img;
        return $this->get_url($file, $this->_s_image_dir, $admin, $ssl, $native_img);
    }
    /**
     * Finds and returns image folders path
     *
     * @param bool $admin Whether to force admin
     *
     * @return string
     */
    public function get_image_dir($admin = false)
    {
        return $this->get_dir(null, $this->_s_image_dir, $admin);
    }
    /**
     * Finds and returns product pictures files or folders path
     *
     * @param string $file  File name
     * @param bool   $admin Whether to force admin
     * @param int    $lang  Language
     * @param int    $shop  Shop id
     * @param string $theme theme name
     *
     * @return string
     */
    public function get_picture_path($file, $admin = false, $lang = null, $shop = null, $theme = null)
    {
        return $this->get_dir($file, $this->_s_picture_dir, $admin, $lang, $shop, $theme);
    }
    /**
     * Finds and returns master pictures folder path
     *
     * @param bool $admin Whether to force admin
     *
     * @return string
     */
    public function get_master_picture_dir($admin = false)
    {
        return $this->get_dir(null, $this->_s_picture_dir . '/' . $this->_s_master_picture_dir, $admin);
    }
    /**
     * Finds and returns master picture path
     *
     * @param string $file  File name
     * @param bool   $admin Whether to force admin
     *
     * @return string
     */
    public function get_master_picture_path($file, $admin = false)
    {
        return $this->get_dir($file, $this->_s_picture_dir . '/' . $this->_s_master_picture_dir, $admin);
    }
    /**
     * Finds and returns product picture file or folder url
     *
     * @param string $file   File name
     * @param bool   $admin  Whether to force admin
     * @param bool   $ssl    Whether to force ssl
     * @param int    $lang   Language
     * @param int    $shopId Shop id
     * @param string $defPic Default (nopic) image path ["0/nopic.jpg"]
     *
     * @return string
     */
    public function get_picture_url($file, $admin = false, $ssl = null, $lang = null, $shop_id = null, $def_pic = 'master/nopic.jpg')
    {
        if ($alt_url = Registry::get_picture_handler()->get_alt_image_url('', $file)) {
            return $alt_url;
        }
        $native_img = $this->get_config_param('blNativeImages');
        $url = $this->get_url($file, $this->_s_picture_dir, $admin, $ssl, $native_img, $lang, $shop_id);
        //anything is better than empty name, because <img src=""> calls shop once more = x2 SLOW.
        if (!$url && $def_pic) {
            return $this->get_url($def_pic, $this->_s_picture_dir, $admin, $ssl, $native_img, $lang, $shop_id);
        }
        return $url;
    }
    /**
     * Finds and returns product pictures folders path
     *
     * @param bool $admin Whether to force admin
     *
     * @return string
     */
    public function get_picture_dir($admin)
    {
        return $this->get_dir(null, $this->_s_picture_dir, $admin);
    }
    /**
     * Calculates and returns full path to template.
     *
     * @param string $templateName Template name
     * @param bool   $isAdmin      Whether to force admin
     *
     * @return string
     */
    public function get_template_path($template_name, $is_admin)
    {
        return $this->get_dir($template_name, $this->_s_template_dir, $is_admin);
    }
    /**
     * Finds and returns templates folders path
     *
     * @param bool $admin Whether to force admin
     *
     * @return string
     */
    public function get_template_dir($admin = false)
    {
        return $this->get_dir(null, $this->_s_template_dir, $admin);
    }
    /**
     * Finds and returns template file or folder url
     *
     * @param string $file  File name
     * @param bool   $admin Whether to force admin
     * @param bool   $ssl   Whether to force ssl
     * @param int    $lang  Language id
     *
     * @return string
     */
    public function get_template_url($file = null, $admin = false, $ssl = null, $lang = null)
    {
        return $this->get_shop_main_url() . $this->get_dir($file, $this->_s_template_dir, $admin, $lang, null, null, false);
    }
    /**
     * Finds and returns base template folder url
     *
     * @param bool $admin Whether to force admin
     *
     * @return string
     */
    public function get_template_base($admin = false)
    {
        // Base template dir is the parent dir of template dir
        return str_replace($this->_s_template_dir . '/', '', $this->get_dir(null, $this->_s_template_dir, $admin, null, null, null, false));
    }
    /**
     * Finds and returns resource (css, js, etc..) files or folders path
     *
     * @param string $file  File name
     * @param bool   $admin Whether to force admin
     *
     * @return string
     */
    public function get_resource_path($file = '', $admin = false)
    {
        return $this->get_dir($file, $this->_s_resource_dir, $admin);
    }
    /**
     * Finds and returns resource (css, js, etc..) file or folder url
     *
     * @param string $file  File name
     * @param bool   $admin Whether to force admin
     * @param bool   $ssl   Whether to force ssl
     * @param int    $lang  Language id
     *
     * @return string
     */
    public function get_resource_url($file = '', $admin = false, $ssl = null, $lang = null)
    {
        $native_img = $this->get_config_param('blNativeImages');
        return $this->get_url($file, $this->_s_resource_dir, $admin, $ssl, $native_img, $lang);
    }
    /**
     * Finds and returns resource (css, js, etc..) folders path
     *
     * @param bool $admin Whether to force admin
     *
     * @return string
     */
    public function get_resource_dir($admin)
    {
        return $this->get_dir(null, $this->_s_resource_dir, $admin);
    }
    /**
     * Returns array of available currencies
     *
     * @param integer $currency Active currency number (default null)
     *
     * @return stdClass[]
     */
    public function get_currency_array($currency = null)
    {
        $conf_currencies = $this->get_config_param('aCurrencies');
        if (!is_array($conf_currencies)) {
            return [];
        }
        // processing currency configuration data
        $currencies = [];
        reset($conf_currencies);
        foreach ($conf_currencies as $key => $val) {
            if ($val) {
                $cur = new stdClass();
                $cur->id = $key;
                $cur_values = explode('@', (string) $val);
                $cur->name = trim($cur_values[0]);
                $cur->rate = trim($cur_values[1]);
                $cur->dec = trim($cur_values[2]);
                $cur->thousand = trim($cur_values[3]);
                $cur->sign = trim($cur_values[4]);
                $cur->decimal = trim($cur_values[5]);
                // change for US version
                if (isset($cur_values[6])) {
                    $cur->side = trim($cur_values[6]);
                }
                if (isset($currency) && $key == $currency) {
                    $cur->selected = 1;
                } else {
                    $cur->selected = 0;
                }
                $currencies[$key] = $cur;
            }
            // #861C -  performance, do not load other currencies
            if (!$this->get_config_param('bl_perfLoadCurrency')) {
                break;
            }
        }
        return $currencies;
    }
    /**
     * Returns currency object.
     *
     * @param string $name Name of active currency
     *
     * @return stdClass|null
     */
    public function get_currency_object($name)
    {
        $search = $this->get_currency_array();
        foreach ($search as $cur) {
            if ($cur->name == $name) {
                return $cur;
            }
        }
    }
    /**
     * Checks if the shop is in demo mode.
     *
     * @return bool
     */
    public function is_demo_shop()
    {
        return Container_Facade::get_parameter('oxid_esales.demo_shop_mode');
    }
    public function get_edition(): Edition
    {
        return Container_Facade::get(Basic_Context_Interface::class)->get_edition();
    }
    /**
     * Returns full eShop edition name
     */
    public function get_full_edition(): string
    {
        return $this->get_edition()->get_full_edition_name();
    }
    /**
     * Returns build package info file content.
     *
     * @return bool|string
     */
    public function get_package_info()
    {
        $file_name = Path::join(Container_Facade::get_parameter('oxid_esales.shop_source_directory'), 'pkg.info');
        $rev = @file_get_contents($file_name);
        $rev = str_replace("\n", '<br>', $rev);
        if (!$rev) {
            return false;
        }
        return $rev;
    }
    /**
     * Counts OXID mandates
     *
     * @return int
     */
    public function get_mandate_count()
    {
        return 1;
    }
    /**
     * Checks if shop is MALL. Returns true on success.
     *
     * @return bool
     */
    public function is_mall()
    {
        return false;
    }
    /**
     * Checks version of shop, returns:
     *  0 - version is bellow 2.2
     *  1 - Demo or unlicensed
     *  2 - Pro
     *  3 - Enterprise
     */
    public function detect_version()
    {
    }
    /**
     * Updates or adds new shop configuration parameters to DB.
     * Arrays must be passed not serialized, serialized values are supported just for backward compatibility.
     *
     * @param string $varType Variable Type
     * @param string $varName Variable name
     * @param mixed  $varVal  Variable value (can be string, integer or array)
     * @param int    $shopId  Shop ID, default is current shop
     * @param string $module  Module name (empty for base options)
     */
    public function save_shop_conf_var($var_type, $var_name, $var_val, $shop_id = null, $module = ''): void
    {
        switch ($var_type) {
            case 'arr':
            case 'aarr':
                $value = serialize($var_val);
                break;
            case 'bool':
                //config param
                $var_val = ($var_val == 'true' || $var_val) && $var_val && strcasecmp((string) $var_val, 'false');
                //db value
                $value = $var_val ? '1' : '';
                break;
            case 'num':
                //config param
                $var_val = $var_val != '' ? Registry::get_utils()->string2Float($var_val) : '';
                $value = $var_val;
                break;
            default:
                $value = $var_val;
                break;
        }
        if (!$shop_id) {
            $shop_id = $this->get_shop_id();
        }
        // Update value only for current shop
        if ($shop_id == $this->get_shop_id()) {
            $this->set_config_param($var_name, $var_val);
        }
        $db = \Oxid_Esales\Eshop\Core\Database_Provider::get_db();
        $new_oxid = \Oxid_Esales\Eshop\Core\Registry::get_utils_object()->generate_uid();
        $query = 'delete from oxconfig where oxshopid = :oxshopid and oxvarname = :oxvarname and oxmodule = :oxmodule';
        $db->execute($query, ['oxshopid' => $shop_id, 'oxvarname' => $var_name, 'oxmodule' => $module ?: '']);
        $query = 'insert into oxconfig (oxid, oxshopid, oxmodule, oxvarname, oxvartype, oxvarvalue)
                  values (:oxid, :oxshopid, :oxmodule, :oxvarname, :oxvartype, :value)';
        $db->execute($query, ['oxid' => $new_oxid, 'oxshopid' => $shop_id, 'oxmodule' => $module ?: '', 'oxvarname' => $var_name, 'oxvartype' => $var_type, 'value' => $value ?? '']);
        $this->inform_services_after_configuration_changed($var_name, $shop_id, $module);
    }
    /**
     * Retrieves shop configuration parameters from DB.
     *
     * @param string $varName Variable name
     * @param int    $shopId  Shop ID
     * @param string $module  module identifier
     *
     * @return object - raw configuration value in DB
     */
    public function get_shop_conf_var($var_name, $shop_id = null, $module = '')
    {
        if (!$shop_id) {
            $shop_id = $this->get_shop_id();
        }
        if ($shop_id == $this->get_shop_id() && (!$module || $module == Config::OXMODULE_THEME_PREFIX . $this->get_config_param('sTheme'))) {
            $var_value = $this->get_config_param($var_name);
            if ($var_value !== null) {
                return $var_value;
            }
        }
        $db = Database_Provider::get_db();
        $query = 'select oxvartype, oxvarvalue from oxconfig where oxshopid = :oxshopid and oxmodule = :oxmodule and oxvarname = :oxvarname';
        $result = $db->select($query, ['oxshopid' => $shop_id, 'oxmodule' => $module, 'oxvarname' => $var_name]);
        if ($result != false && $result->count() > 0) {
            return $this->decode_value($result->fields['oxvartype'], $result->fields['oxvarvalue']);
        }
    }
    /**
     * Decodes and returns database value
     *
     * @param string $type       parameter type
     * @param mixed  $mOrigValue parameter db value
     *
     * @return mixed
     */
    public function decode_value($type, $m_orig_value)
    {
        $value = $m_orig_value;
        return match ($type) {
            'arr', 'aarr' => unserialize($m_orig_value, ['allowed_classes' => false]),
            'bool' => $m_orig_value == 'true' || $m_orig_value == '1',
            default => $value,
        };
    }
    /**
     * Returns true if current active shop is in productive mode or false if not
     *
     * @return bool
     */
    public function is_productive_mode()
    {
        $productive = $this->get_config_param('blProductive');
        if (!isset($productive)) {
            $query = 'select oxproductive from oxshops where oxid = :oxid';
            $productive = (bool) \Oxid_Esales\Eshop\Core\Database_Provider::get_db()->get_one($query, ['oxid' => $this->get_shop_id()]);
            $this->set_config_param('blProductive', $productive);
        }
        return $productive;
    }
    /**
     * Function returns default shop ID
     *
     * @deprecated
     * @see BasicContextInterface::getDefaultShopId()
     *
     * @return string
     */
    public function get_base_shop_id()
    {
        return \Oxid_Esales\Eshop\Core\Shop_Id_Calculator::BASE_SHOP_ID;
    }
    /**
     * Loads and returns active shop object
     *
     * @return Shop
     */
    public function get_active_shop()
    {
        if ($this->_o_act_shop && $this->_i_shop_id == $this->_o_act_shop->get_id() && $this->_o_act_shop->get_language() == Registry::get_lang()->get_base_language()) {
            return $this->_o_act_shop;
        }
        $this->_o_act_shop = ox_new(\Oxid_Esales\Eshop\Application\Model\Shop::class);
        $this->_o_act_shop->load($this->get_shop_id());
        return $this->_o_act_shop;
    }
    /**
     * Returns active view object. If this object was not defined - returns oxubase object
     *
     * @return FrontendController
     */
    public function get_active_view()
    {
        if (count($this->_a_active_views)) {
            $act_view = end($this->_a_active_views);
        }
        if (!isset($act_view) || $act_view == null) {
            $act_view = ox_new(\Oxid_Esales\Eshop\Application\Controller\Frontend_Controller::class);
            $this->_a_active_views[] = $act_view;
        }
        return $act_view;
    }
    /**
     * Returns top active view object from views chain.
     *
     * @return FrontendController
     */
    public function get_top_active_view()
    {
        if (count($this->_a_active_views)) {
            return reset($this->_a_active_views);
        }
        return $this->get_active_view();
    }
    /**
     * Returns all active views objects list.
     *
     * @return array
     */
    public function get_active_views_list()
    {
        return $this->_a_active_views;
    }
    /**
     * View object setter
     *
     * @param object $view view object
     */
    public function set_active_view($view): void
    {
        $this->_a_active_views[] = $view;
    }
    /**
     * Drop last active view object
     */
    public function drop_last_active_view(): void
    {
        array_pop($this->_a_active_views);
    }
    /**
     * Check if there is more than one active view
     */
    public function has_active_views_chain()
    {
        return count($this->_a_active_views) > 1;
    }
    /**
     * Get active views class id list
     *
     * @return array
     */
    public function get_active_views_ids()
    {
        $ids = [];
        if (is_array($this->get_active_views_list())) {
            foreach ($this->get_active_views_list() as $view) {
                $ids[] = $view->get_class_key();
            }
        }
        return $ids;
    }
    /**
     * Returns log files storage path
     *
     * @return string
     */
    public function get_logs_dir()
    {
        return Path::join(Container_Facade::get_parameter('oxid_esales.shop_source_directory'), 'log');
    }
    /**
     * Returns true if option is theme option
     *
     * @param string $name option name
     *
     * @return bool
     */
    public function is_theme_option($name)
    {
        return isset($this->_a_theme_config_params[$name]);
    }
    /**
     * Returns  SSL or non SSL shop main URL without index.php
     *
     * @return string
     */
    public function get_shop_main_url()
    {
        return Container_Facade::get_parameter('oxid_esales.shop_url');
    }
    /**
     * Return active shop ids
     *
     * @deprecated
     * @see ContextInterface::getAllShopIds()
     *
     * @return array
     */
    public function get_shop_ids()
    {
        return \Oxid_Esales\Eshop\Core\Database_Provider::get_db()->get_col('SELECT `oxid` FROM `oxshops`');
    }
    /**
     * Function returns shop url by given language.
     * #680 per language another URL
     *
     * @param integer $lang Language id.
     * @param bool    $ssl  Whether to use ssl.
     *
     * @return null|string
     */
    public function get_shop_url_by_language($lang, $ssl = false)
    {
        $config_parameter = $ssl ? 'aLanguageSSLURLs' : 'aLanguageURLs';
        $lang ??= Registry::get_lang()->get_base_language();
        $language_ur_ls = $this->get_config_param($config_parameter);
        if (isset($lang) && isset($language_ur_ls[$lang]) && !empty($language_ur_ls[$lang])) {
            $language_ur_ls[$lang] = Registry::get_utils()->check_url_ending_slash($language_ur_ls[$lang]);
            return $language_ur_ls[$lang];
        }
    }
    /**
     * Function returns mall shop url.
     *
     * @return null|string
     */
    public function get_mall_shop_url()
    {
        $mall_shop_url = $this->get_config_param('sMallSSLShopURL');
        if ($mall_shop_url) {
            return Registry::get_utils()->check_url_ending_slash($mall_shop_url);
        }
    }
    /**
     * Handle database exception.
     * At this point everything has crashed already and not much of shop business logic is left to call.
     * So just go straight and call the ExceptionHandler.
     */
    protected function handle_db_connection_exception(\Oxid_Esales\Eshop\Core\Exception\Database_Exception $exception)
    {
        $this->get_exception_handler()->handle_uncaught_exception($exception);
    }
    /**
     * Redirect to start page and display the error
     *
     * @param \OxidEsales\Eshop\Core\Exception\StandardException $ex message to show on exit
     */
    protected function handle_cookie_exception($ex)
    {
        $this->process_seo_call();
        //starting up the session
        $session = \Oxid_Esales\Eshop\Core\Registry::get_session();
        $session->start();
        // redirect to start page and display the error
        Registry::get_utils_view()->add_error_to_display($ex);
        Registry::get_utils()->redirect($this->get_shop_home_url() . 'cl=start', true, 302);
    }
    /**
     * Save system configuration parameters, which is the same for sub-shops.
     *
     * @param string $parameterType  Type
     * @param string $parameterName  Name
     * @param mixed  $parameterValue Value (can be string, integer or array)
     */
    public function save_system_config_parameter($parameter_type, $parameter_name, $parameter_value): void
    {
        $this->save_shop_conf_var($parameter_type, $parameter_name, $parameter_value, $this->get_base_shop_id());
    }
    /**
     * Retrieves system configuration parameters, which is the same for sub-shops.
     *
     * @param string $parameterName Variable name
     *
     * @return mixed
     */
    public function get_system_config_parameter($parameter_name)
    {
        return $this->get_shop_conf_var($parameter_name, $this->get_base_shop_id());
    }
    /**
     * Returns whether given shop id is valid.
     *
     * @param int    $shopId
     *
     * @return bool
     */
    protected function is_valid_shop_id($shop_id)
    {
        return !empty($shop_id);
    }
    /**
     * Returns active shop id.
     *
     * @return string
     */
    protected function calculate_active_shop_id()
    {
        return $this->get_base_shop_id();
    }
    /**
     * Check and get template path by Edition if exists
     *
     * @param string $templateName
     *
     * @return false|string
     */
    protected function get_edition_template($template_name)
    {
        return false;
    }
    /**
     * @return \OxidEsales\Eshop\Core\Exception\ExceptionHandler
     */
    protected function get_exception_handler()
    {
        return new \Oxid_Esales\Eshop\Core\Exception\Exception_Handler();
    }
    /**
     * Inform respective services if shop/module/theme related configuration data was changed in database.
     *
     * @param string  $varName   Variable name
     * @param integer $shopId    Shop id
     * @param string  $extension Module or theme name in case of extension config change
     */
    protected function inform_services_after_configuration_changed($var_name, $shop_id, $extension = '')
    {
        if (empty($extension)) {
            Container_Facade::dispatch(new Shop_Configuration_Changed_Event($var_name, (int) $shop_id));
        } elseif (str_contains($extension, self::OXMODULE_THEME_PREFIX)) {
            Container_Facade::dispatch(new Theme_Setting_Changed_Event($var_name, (int) $shop_id, $extension));
        }
    }
}