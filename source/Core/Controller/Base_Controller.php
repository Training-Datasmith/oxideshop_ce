<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Core\Controller;

use Oxid_Esales\Eshop\Core\Registry;
use Oxid_Esales\Eshop_Community\Core\Di\Container_Facade;
use Oxid_Esales\Eshop_Community\Core\Shop_Version;
use Oxid_Esales\Eshop_Community\Internal\Framework\Controller\View_Controller_Interface;
use Oxid_Esales\Eshop_Community\Internal\Transition\Shop_Events\After_Request_Processed_Event;
/**
 * Base view class. Collects and passes data to template engine, sets some global
 * configuration parameters.
 */
class Base_Controller extends \Oxid_Esales\Eshop\Core\Base implements View_Controller_Interface
{
    /**
     * Array of data that is passed to template engine - array( "varName" => "varValue").
     *
     * @var array
     */
    protected $_a_view_data = [];
    /**
     * View parameters array
     *
     * @var array
     */
    protected $_a_view_params = [];
    /**
     * Location of a executed class file.
     *
     * @var string
     */
    protected $_s_class_location;
    /**
     * Name of running class method.
     *
     * @var string
     */
    protected $_s_this_action;
    /**
     * If this is a component we will have our parent view here.
     *
     * @var \OxidEsales\Eshop\Core\Controller\BaseController|null
     */
    protected $_o_parent;
    /**
     * Flag if this object is a component or not
     *
     * @var bool|null
     */
    protected $_bl_is_component = false;
    /**
     * Name of template file to render.
     *
     * @var string
     */
    protected $_s_this_template;
    /**
     * ID of current view - generated php file.
     *
     * @var string
     */
    protected $_s_view_id;
    /**
     * Current view class name
     *
     * @var string
     */
    protected $_s_class;
    /**
     * Current view class key
     *
     * @var string
     */
    protected $class_key;
    /**
     * Action function name
     *
     * @var string
     */
    protected $_s_fnc;
    /**
     * Marker if user defined function was executed
     *
     * @var bool
     */
    protected static $_bl_executed = false;
    /**
     * Active charset
     *
     * @var string
     */
    protected $_s_char_set;
    /**
     * Shop version
     *
     * @var string
     */
    protected $_s_version;
    /**
     * If current shop has demo version
     *
     * @var bool
     */
    protected $_bl_demo_version;
    /**
     * If current shop has demo shop
     *
     * @var bool
     */
    protected $_bl_demo_shop;
    /**
     * Display if newsletter must be displayed
     *
     * @var bool
     */
    protected $_i_news_status;
    /**
     * Shop logo
     *
     * @var string
     */
    protected $_s_shop_logo;
    /**
     * Category ID
     *
     * @var string
     */
    protected $_s_category_id;
    /**
     * Active category object.
     *
     * @var object
     */
    protected $_o_click_cat;
    /**
     * Cache sign to enable/disable use of cache.
     *
     * @var bool
     */
    protected $_bl_is_call_for_cache = false;
    /**
     * \OxidEsales\Eshop\Core\ViewConfig instance
     *
     * @var \OxidEsales\Eshop\Core\ViewConfig
     */
    protected $_o_view_conf;
    /**
     * Initiates all components stored, executes \OxidEsales\Eshop\Core\Controller\BaseController::addGlobalParams.
     */
    public function init(): void
    {
        // setting current view class name
        $this->_s_this_action = strtolower(static::class);
        if (!$this->_bl_is_component) {
            // assume that cached components does not affect this method ...
            $this->add_global_params();
        }
    }
    /**
     * Add parameters to controllers
     *
     * @param array $aParams view parameters array.
     */
    public function set_view_parameters($a_params = null): void
    {
        $this->_a_view_params = $a_params;
    }
    /**
     * Get parameters to controllers
     *
     * @param string $sKey parameter key
     *
     * @return string
     */
    public function get_view_parameter($s_key)
    {
        return $this->_a_view_params[$s_key] ?? Registry::get_request()->get_request_escaped_parameter($s_key);
    }
    /**
     * Set cache sign to enable/disable use of cache
     *
     * @param bool $blIsCallForCache cache sign to enable/disable use of cache
     */
    public function set_is_call_for_cache($bl_is_call_for_cache = null): void
    {
        $this->_bl_is_call_for_cache = $bl_is_call_for_cache;
    }
    /**
     * Get cache sign to enable/disable use of cache
     *
     * @return bool
     */
    public function get_is_call_for_cache()
    {
        return $this->_bl_is_call_for_cache;
    }
    /**
     * Returns view ID (currently it returns NULL)
     */
    public function get_view_id()
    {
    }
    /**
     * Entry point to pass controller-specific data to the view.
     * @return string current view template file name
     */
    public function render()
    {
        return $this->get_template_name();
    }
    /**
     * Sets and caches default parameters for shop object and returns it.
     *
     * Template variables:
     * <b>isdemoversion</b>, <b>shop</b>, <b>isdemoversion</b>,
     * <b>version</b>,
     * <b>urlsign</b>
     *
     * @param \OxidEsales\Eshop\Application\Model\Shop $oShop current shop object
     *
     * @return \OxidEsales\Eshop\Core\ViewConfig $oShop current shop object
     */
    public function add_global_params($o_shop = null)
    {
        // by default we always display newsletter bar
        $this->_i_news_status = 1;
        // assigning shop to view config ..
        $o_view_conf = $this->get_view_config();
        if ($o_shop) {
            $o_view_conf->set_view_shop($o_shop, $this->_a_view_data);
        }
        //sending all view to template engine
        $this->_a_view_data['oView'] = $this;
        $this->_a_view_data['oViewConf'] = $this->get_view_config();
        return $o_view_conf;
    }
    /**
     * Sets value to parameter used by template engine.
     *
     * @param string $sPara  name of parameter to pass
     * @param mixed  $sValue value of parameter
     */
    public function add_tpl_param($s_para, $s_value): void
    {
        $this->_a_view_data[$s_para] = $s_value;
    }
    /**
     * Returns view config object
     *
     * @return \OxidEsales\Eshop\Core\ViewConfig
     */
    public function get_view_config()
    {
        if ($this->_o_view_conf === null) {
            $this->_o_view_conf = ox_new(\Oxid_Esales\Eshop\Core\View_Config::class);
        }
        return $this->_o_view_conf;
    }
    /**
     * Returns current view template file name
     *
     * @return string
     */
    public function get_template_name()
    {
        return $this->_s_this_template;
    }
    /**
     * Sets current view template file name
     *
     * @param string $sTemplate template name
     */
    public function set_template_name($s_template): void
    {
        $this->_s_this_template = $s_template;
    }
    /**
     * Current view class key setter.
     *
     * @param string $classKey current view class key
     */
    public function set_class_key($class_key): void
    {
        $this->_s_class = $class_key;
        $this->class_key = $class_key;
    }
    /**
     * Returns class key of current view
     *
     * @return string
     */
    public function get_class_key()
    {
        return $this->class_key;
    }
    /**
     * Set current view action function name
     *
     * @param string $sFncName action function name
     */
    public function set_fnc_name($s_fnc_name): void
    {
        $this->_s_fnc = $s_fnc_name;
    }
    /**
     * Returns name of current action function
     *
     * @return string
     */
    public function get_fnc_name()
    {
        return $this->_s_fnc;
    }
    /**
     * Set array of data that is passed to template engine - array( "varName" => "varValue")
     *
     * @param array $aViewData array of data that is passed to template engine
     */
    public function set_view_data($a_view_data = null): void
    {
        $this->_a_view_data = $a_view_data;
    }
    /**
     * Get view data
     *
     * @return array
     */
    public function get_view_data()
    {
        return $this->_a_view_data;
    }
    /**
     * Get view data single array element
     *
     * @param string $sParamId view data array key
     *
     * @return mixed
     */
    public function get_view_data_element($s_param_id = null)
    {
        if ($s_param_id && isset($this->_a_view_data[$s_param_id])) {
            return $this->_a_view_data[$s_param_id];
        }
        return null;
    }
    /**
     * Set location of a executed class file
     *
     * @param string $sClassLocation location of a executed class file
     */
    public function set_class_location($s_class_location = null): void
    {
        $this->_s_class_location = $s_class_location;
    }
    /**
     * Get location of a executed class file
     *
     * @return string
     */
    public function get_class_location()
    {
        return $this->_s_class_location;
    }
    /**
     * Set name of running class method
     *
     * @param string $sThisAction name of running class method
     */
    public function set_this_action($s_this_action = null): void
    {
        $this->_s_this_action = $s_this_action;
    }
    /**
     * Get name of running class method
     *
     * @return string
     */
    public function get_this_action()
    {
        return $this->_s_this_action;
    }
    /**
     * Set parent object. If this is a component we will have our parent view here.
     * @param \OxidEsales\Eshop\Core\Controller\BaseController $oParent parent object
     */
    public function set_parent($o_parent = null): void
    {
        $this->_o_parent = $o_parent;
    }
    /**
     * Get parent object
     *
     * @return \OxidEsales\Eshop\Core\Controller\BaseController|null
     */
    public function get_parent()
    {
        return $this->_o_parent;
    }
    /**
     * Set flag if this object is a component or not
     *
     * @param bool|null $blIsComponent flag if this object is a component
     */
    public function set_is_component($bl_is_component = null): void
    {
        $this->_bl_is_component = $bl_is_component;
    }
    /**
     * Get flag if this object is a component
     *
     * @return bool|null
     */
    public function get_is_component()
    {
        return $this->_bl_is_component;
    }
    /**
     * Executes method (creates class and then executes). Returns executed
     * function result.
     *
     * @param string $sFunction name of function to execute
     *
     * @throws \OxidEsales\Eshop\Core\Exception\SystemComponentException system component exception
     */
    public function execute_function($s_function): void
    {
        // execute
        if ($s_function && !self::$_bl_executed) {
            if (method_exists($this, $s_function)) {
                $s_new_action = $this->{$s_function}();
                self::$_bl_executed = true;
                Container_Facade::dispatch(new After_Request_Processed_Event());
                if (isset($s_new_action)) {
                    $this->execute_new_action($s_new_action);
                }
            } elseif (!$this->_bl_is_component) {
                throw new \Oxid_Esales\Eshop\Core\Exception\Routing_Exception(sprintf('Controller method is not accessible: %s::%s', self::class, $s_function));
            }
        }
    }
    /**
     * Formats header for new controller action
     *
     * Input example: "view_name?param1=val1&param2=val2" => "cl=view_name&param1=val1&param2=val2"
     *
     * @param string $sNewAction new action params
     *
     * @throws \OxidEsales\Eshop\Core\Exception\SystemComponentException system component exception
     */
    protected function execute_new_action($s_new_action)
    {
        if ($s_new_action) {
            $my_config = \Oxid_Esales\Eshop\Core\Registry::get_config();
            // page parameters is the part which goes after '?'
            $params = explode('?', $s_new_action);
            // action parameters is the part before '?'
            $page_params = $params[1] ?? null;
            // looking for function name
            $params = explode('/', $params[0]);
            $class_name = $params[0];
            $resolved_class_name = \Oxid_Esales\Eshop\Core\Registry::get_controller_class_name_resolver()->get_class_name_by_id($class_name);
            $real_class_name = $resolved_class_name ? \Oxid_Esales\Eshop\Core\Registry::get_utils_object()->get_class_name($resolved_class_name) : \Oxid_Esales\Eshop\Core\Registry::get_utils_object()->get_class_name($class_name);
            if (false === class_exists($real_class_name)) {
                //If redirect tries to use a not existing class throw an exception.
                //we'll be redirected to start page directly.
                $exception = new \Oxid_Esales\Eshop\Core\Exception\System_Component_Exception();
                /** Use setMessage here instead of passing it in constructor in order to test exception message */
                $exception->set_message('ERROR_MESSAGE_SYSTEMCOMPONENT_CLASSNOTFOUND' . ' ' . $class_name);
                $exception->set_component($class_name);
                throw $exception;
            }
            $session = \Oxid_Esales\Eshop\Core\Registry::get_session();
            // building redirect path ...
            $header = $class_name ? "cl={$class_name}&" : '';
            // adding view name
            $header .= $page_params ? "{$page_params}&" : '';
            // adding page params
            $header .= $session->sid();
            // adding session Id
            $url = $my_config->get_current_shop_url($this->is_admin());
            $url = "{$url}index.php?{$header}";
            $url = \Oxid_Esales\Eshop\Core\Registry::get_utils_url()->process_url($url);
            if (\Oxid_Esales\Eshop\Core\Registry::get_utils()->seo_is_active() && $seo_url = \Oxid_Esales\Eshop\Core\Registry::get_seo_encoder()->get_static_url($url)) {
                $url = $seo_url;
            }
            $this->on_execute_new_action();
            Container_Facade::dispatch(new After_Request_Processed_Event());
            //#M341 do not add redirect parameter
            \Oxid_Esales\Eshop\Core\Registry::get_utils()->redirect($url, (bool) Registry::get_request()->get_request_escaped_parameter('redirected'), 302);
        }
    }
    /**
     * Method for overwriting if any additional actions on _executeNewAction is needed
     */
    protected function on_execute_new_action()
    {
    }
    /**
     * Template variable getter. Returns additional params for url
     *
     * @return string
     */
    public function get_additional_params()
    {
        return \Oxid_Esales\Eshop\Core\Registry::get_utils_url()->process_url('', false);
    }
    /**
     * Returns active charset
     *
     * @return string
     */
    public function get_char_set()
    {
        if ($this->_s_char_set == null) {
            $this->_s_char_set = \Oxid_Esales\Eshop\Core\Registry::get_lang()->translate_string('charset');
        }
        return $this->_s_char_set;
    }
    /**
     * Returns shop version
     *
     * @return string
     */
    public function get_shop_version()
    {
        return Shop_Version::get_version();
    }
    /**
     * Returns shop edition
     *
     * @return string
     */
    public function get_shop_edition()
    {
        return Registry::get_config()->get_edition()->value;
    }
    /**
     * Returns shop package info
     *
     * @return string
     */
    public function get_package_info()
    {
        return \Oxid_Esales\Eshop\Core\Registry::get_config()->get_package_info();
    }
    /**
     * Returns shop full edition
     *
     * @return string
     */
    public function get_shop_full_edition()
    {
        $s_edition = $this->get_shop_edition();
        if ($s_edition == 'PE') {
            return 'Professional Edition';
        }
        if ($s_edition == 'EE') {
            return 'Enterprise Edition';
        }
        return 'Community Edition';
    }
    /**
     * Returns if current shop is demo version
     *
     * @return string
     */
    public function is_demo_version()
    {
        if ($this->_bl_demo_version == null) {
            $this->_bl_demo_version = \Oxid_Esales\Eshop\Core\Registry::get_config()->detect_version() == 1;
        }
        return $this->_bl_demo_version;
    }
    /**
     * Returns if current shop is beta version.
     *
     * @return bool
     */
    public function is_beta_version()
    {
        return stripos($this->get_shop_version(), 'beta') !== false;
    }
    /**
     * Returns if current shop is release candidate version.
     *
     * @return bool
     */
    public function is_rc_version()
    {
        return stripos($this->get_shop_version(), 'rc') !== false;
    }
    /**
     * Returns if current shop is demo shop
     *
     * @return string
     */
    public function is_demo_shop()
    {
        if ($this->_bl_demo_shop == null) {
            $this->_bl_demo_shop = \Oxid_Esales\Eshop\Core\Registry::get_config()->is_demo_shop();
        }
        return $this->_bl_demo_shop;
    }
    /**
     * Template variable getter. Returns if newsletter can be displayed (for _right)
     *
     * @return integer
     */
    public function show_newsletter()
    {
        return $this->_i_news_status ?? 1;
    }
    /**
     * Sets if to show newsletter
     *
     * @param bool $blShow if TRUE - newsletter subscription box will be shown
     */
    public function set_show_newsletter($bl_show): void
    {
        $this->_i_news_status = $bl_show;
    }
    /**
     * Returns active category set by categories component; if category is
     * not set by component - will create category object and will try to
     * load by id passed by request
     *
     * @return \OxidEsales\Eshop\Application\Model\Category
     */
    public function get_act_category()
    {
        // if active category is not set yet - trying to load it from request params
        // this may be usefull when category component was unable to load active category
        // and we still need some object to mount navigation info
        if ($this->_o_click_cat === null) {
            $this->_o_click_cat = false;
            $o_category = ox_new(\Oxid_Esales\Eshop\Application\Model\Category::class);
            if ($o_category->load($this->get_category_id())) {
                $this->_o_click_cat = $o_category;
            }
        }
        return $this->_o_click_cat;
    }
    /**
     * Active category setter
     *
     * @param \OxidEsales\Eshop\Application\Model\Category $oCategory active category
     */
    public function set_act_category($o_category): void
    {
        $this->_o_click_cat = $o_category;
    }
    /**
     * Get category ID
     *
     * @return string
     */
    public function get_category_id()
    {
        if ($this->_s_category_id == null && $s_cat_id = Registry::get_request()->get_request_escaped_parameter('cnid')) {
            $this->_s_category_id = $s_cat_id;
        }
        return $this->_s_category_id;
    }
    /**
     * Category ID setter
     *
     * @param string $sCategoryId Id of category to cache
     */
    public function set_category_id($s_category_id): void
    {
        $this->_s_category_id = $s_category_id;
    }
    /**
     * Returns a name of the view variable containing the error/exception messages
     */
    public function get_error_destination()
    {
    }
    /**
     * Returns name of a view class, which will be active for an action
     * (given a generic fnc, e.g. logout)
     *
     * @return string
     */
    public function get_action_class_name()
    {
        return $this->get_class_key();
    }
    /**
     * Returns if shop is mall
     *
     * @return bool
     */
    public function is_mall()
    {
        return false;
    }
    /**
     * Returns if page has rdfa
     *
     * @return bool
     */
    public function show_rdfa()
    {
        return false;
    }
    /**
     * Returns session ID, but only in case it is needed to be included for widget calls.
     * This basically happens on session change,
     * when session cookie is not equals to the actual session ID.
     *
     * @return string|null
     */
    public function get_sid_for_widget()
    {
        $session = \Oxid_Esales\Eshop\Core\Registry::get_session();
        if (!$session->is_actual_sid_in_cookie()) {
            return $session->get_id();
        }
        return null;
    }
    /**
     * Returns whether to show persistent parameter. Returns true as a default.
     *
     * @param string $persParamKey
     *
     * @return bool
     */
    public function show_pers_param($pers_param_key)
    {
        return true;
    }
}