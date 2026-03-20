<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Core;

use Oxid_Esales\Eshop\Application\Model\Basket;
use Oxid_Esales\Eshop\Application\Model\Basket_Item;
use Oxid_Esales\Eshop\Application\Model\User;
use Oxid_Esales\Eshop\Core\Registry;
use Oxid_Esales\Eshop\Core\Str;
use Oxid_Esales\Eshop_Community\Core\Di\Container_Facade;
/**
 * Session manager.
 * Performs session managing function, such as variables deletion,
 * initialisation and other session functions.
 */
class Session extends \Oxid_Esales\Eshop\Core\Base
{
    /**
     * Session parameter name
     *
     * @var string
     */
    protected $_s_name = 'sid';
    /**
     * Session parameter name
     *
     * @var string
     */
    protected $_s_forced_prefix = 'force_';
    /**
     * Unique session ID.
     *
     * @var string
     */
    protected $_s_id;
    /**
     * A flag indicating that session was just created, useful for tracking cookie support
     *
     * @var bool
     */
    protected static $_bl_is_new_session = false;
    /**
     * Active session user object
     *
     * @var object
     */
    protected static $_o_user;
    /**
     * Indicates if setting of session id is executed in this script. After page transition
     * This needed to be checked as new session is not written in db until it is closed
     *
     * @var bool
     */
    protected $_bl_new_session = false;
    /**
     * Forces session to be started and skips checking if session is allowed
     *
     * @var bool
     */
    protected $_bl_force_new_session = false;
    /**
     * Error message, used for debug purposes only
     *
     * @var string
     */
    protected $_s_error_msg;
    /**
     * Basket session object
     *
     * @var object
     */
    protected $_o_basket;
    /**
     * Basket reservations object
     *
     * @var object
     */
    protected $_o_basket_reservations;
    /**
     * Force session start by defined parameter rules.
     * First level array keys are parameters to check which point to
     * array of values which need session.
     *
     * @var array
     * @see _getRequireSessionWithParams()
     */
    protected $_a_require_session_with_params = ['cl' => ['register' => true, 'account' => true], 'fnc' => ['tobasket' => true, 'login_noredirect' => true, 'tocomparelist' => true], '_artperpage' => true, 'ldtype' => true, 'listorderby' => true];
    /**
     * Marker if processed urls must contain SID parameter
     *
     * @var bool
     */
    protected $_bl_sid_needed;
    /**
     * Session params to be kept even after session timeout
     *
     * @var array
     */
    protected $_a_persistent_params = ['actshop', 'lang', 'currency', 'language', 'tpllanguage'];
    /**
     * Order steps which should not accept force_sid
     */
    private array $order_controllers = ['payment', 'order', 'thankyou'];
    /**
     * Returns session ID
     *
     * @return string
     */
    public function get_id()
    {
        return $this->_s_id;
    }
    /**
     * Sets session id
     *
     * @param string $sVal id value
     */
    public function set_id($s_val): void
    {
        $this->_s_id = $s_val;
    }
    /**
     * Sets session param name
     *
     * @param string $sVal name value
     */
    public function set_name($s_val): void
    {
        $this->_s_name = $s_val;
    }
    /**
     * Returns forced session id param name
     *
     * @return string
     */
    public function get_forced_name()
    {
        return $this->_s_forced_prefix . $this->get_name();
    }
    /**
     * Returns session param name
     *
     * @return string
     */
    public function get_name()
    {
        return $this->_s_name;
    }
    /**
     * retrieves the session id from the request if any
     *
     * @return string|null
     */
    protected function get_sid_from_request()
    {
        Registry::get_config();
        $sid = null;
        $force_sid_param = null;
        if (!$this->is_force_sid_blocked() && !in_array(Registry::get_request()->get_request_escaped_parameter('cl'), $this->order_controllers)) {
            $force_sid_param = Registry::get_request()->get_request_escaped_parameter($this->get_forced_name());
        }
        $sid_param = Registry::get_request()->get_request_escaped_parameter($this->get_name());
        //forcing sid for SSL<->nonSSL transitions
        if ($force_sid_param) {
            $sid = $force_sid_param;
        } elseif ($this->get_session_use_cookies() && $this->get_cookie_sid()) {
            $sid = $this->get_cookie_sid();
        } elseif ($sid_param) {
            $sid = $sid_param;
        }
        return $sid;
    }
    /**
     * Starts shop session, generates unique session ID, extracts user IP.
     */
    public function start(): void
    {
        $this->set_name($this->is_admin() ? 'admin_sid' : 'sid');
        $sid = $this->get_sid_from_request();
        if ($sid) {
            $this->set_id($sid);
        }
        if ($this->is_session_started() === false && $this->allow_session_start()) {
            if (!$sid) {
                self::$_bl_is_new_session = true;
                $this->init_new_session();
            } else {
                self::$_bl_is_new_session = false;
                $this->set_session_id($sid);
                $this->session_start();
            }
            //special handling for new ZP cluster session, as in that case session_start() regenerates id
            if ($this->get_id() !== session_id()) {
                $this->set_id(session_id());
            }
            //checking for swapped client
            $bl_swapped = $this->is_swapped_client();
            if (!self::$_bl_is_new_session && $bl_swapped) {
                $this->init_new_session();
                if ($this->_s_error_msg && Container_Facade::get_parameter('oxid_esales.debug_mode')) {
                    Registry::get_utils_view()->add_error_to_display(ox_new(\Oxid_Esales\Eshop\Core\Exception\Standard_Exception::class, $this->_s_error_msg));
                }
            } elseif (!$bl_swapped) {
                // transferring cookies between hosts
                Registry::get_utils_server()->load_session_cookies();
            }
        }
    }
    /**
     * retrieve session challenge token from request
     *
     * @return string
     */
    public function get_request_challenge_token()
    {
        return preg_replace('/[^a-z0-9]/i', '', Registry::get_request()->get_request_escaped_parameter('stoken') ?? '');
    }
    /**
     * retrieve session challenge token from session
     *
     * @return string
     */
    public function get_session_challenge_token()
    {
        $s_ret = preg_replace('/[^a-z0-9]/i', '', $this->get_variable('sess_stoken') ?? '');
        if (!$s_ret) {
            $this->init_new_session_challenge();
            $s_ret = $this->get_variable('sess_stoken');
        }
        return $s_ret;
    }
    /**
     * check for CSRF, returns true, if request (get/post) token matches session saved var
     * false, if CSRF is possible
     *
     * @return bool
     */
    public function check_session_challenge()
    {
        $s_token = $this->get_session_challenge_token();
        return $s_token && $s_token == $this->get_request_challenge_token();
    }
    /**
     * initialize new session challenge token
     */
    protected function init_new_session_challenge()
    {
        $this->set_variable('sess_stoken', bin2hex(random_bytes(16)));
    }
    /**
     * Initialize session data (calls php::session_start())
     *
     * @return bool
     */
    protected function session_start()
    {
        if ($this->need_to_set_headers()) {
            //enforcing no caching when session is started
            session_cache_limiter('nocache');
        } else {
            session_cache_limiter('');
        }
        session_start();
        if (!$this->get_session_challenge_token()) {
            $this->init_new_session_challenge();
        }
        return $this->is_session_started();
    }
    /**
     * Assigns new session ID, clean existing data except persistent.
     */
    public function init_new_session(): void
    {
        if (!$this->is_session_started()) {
            $this->session_start();
        }
        //saving persistent params if old session exists
        $a_persistent = [];
        foreach ($this->_a_persistent_params as $s_param) {
            if ($s_value = $this->get_variable($s_param)) {
                $a_persistent[$s_param] = $s_value;
            }
        }
        $session_id = $this->get_new_session_id();
        $this->set_id($session_id);
        $this->set_session_cookie($session_id);
        //restoring persistent params to session
        foreach ($a_persistent as $s_key => $s_param) {
            $this->set_variable($s_key, $a_persistent[$s_key]);
        }
        $this->init_new_session_challenge();
        // (re)setting actual user agent when initiating new session
        $this->set_variable('sessionagent', Registry::get_utils_server()->get_server_var('HTTP_USER_AGENT'));
    }
    /**
     * Regenerates session id
     */
    public function regenerate_session_id(): void
    {
        if (!$this->is_session_started()) {
            $this->session_start();
            // (re)setting actual user agent when initiating new session
            $this->set_variable('sessionagent', Registry::get_utils_server()->get_server_var('HTTP_USER_AGENT'));
        }
        $session_id = $this->get_new_session_id(false);
        $this->set_id($session_id);
        $this->set_session_cookie($session_id);
        $this->init_new_session_challenge();
    }
    /**
     * Update the current session id with a newly generated one, deletes the
     * old associated session file, frees all session variables.
     *
     * @param bool $blUnset if true, calls session_unset [optional]
     *
     * @return string
     */
    protected function get_new_session_id($bl_unset = true)
    {
        session_regenerate_id(true);
        if ($bl_unset) {
            session_unset();
        }
        return session_id();
    }
    /**
     * Ends the current session and store session data.
     */
    public function freeze(): void
    {
        // storing basket ..
        $this->set_variable($this->get_basket_name(), serialize($this->get_basket()));
        session_write_close();
    }
    /**
     * Destroys all data registered to a session.
     */
    public function destroy(): void
    {
        unset($_SESSION);
        session_destroy();
    }
    /**
     * @deprecated use SessionInterface::has() instead
     *
     * Checks if variable is set in session. Returns true on success.
     *
     * @param string $name Name to check
     *
     * @return bool
     */
    public function has_variable($name)
    {
        return isset($_SESSION[$name]);
    }
    /**
     * @deprecated use SessionInterface::set() instead
     *
     * Sets parameter and its value to global session mixedvar array.
     *
     * @param string $name  Name of parameter to store
     * @param mixed  $value Value of parameter
     */
    public function set_variable($name, $value): void
    {
        $_SESSION[$name] = $value;
    }
    /**
     * @deprecated use SessionInterface::get() instead
     *
     * IF available returns value of parameter, stored in session array.
     *
     * @param string $name Name of parameter
     *
     * @return mixed
     */
    public function get_variable($name)
    {
        return $_SESSION[$name] ?? null;
    }
    /**
     * @deprecated use SessionInterface::remove() instead
     *
     * Destroys a single element (passed to method) of an session array.
     *
     * @param string $name Name of parameter to destroy
     */
    public function delete_variable($name): void
    {
        $_SESSION[$name] = null;
        unset($_SESSION[$name]);
    }
    /**
     * Returns string prefix to URL with session ID parameter. In some cases
     * (if client is robot, such as Google) adds parameter shp, to identify,
     * witch shop is currently running.
     *
     * @param bool $blForceSid forces sid getter, ignores cookie check (optional)
     *
     * @return string
     */
    public function sid($bl_force_sid = false)
    {
        $my_config = Registry::get_config();
        $s_ret = '';
        $bl_disable_sid = Registry::get_utils()->is_search_engine() && is_array($my_config->get_config_param('aCacheViews')) && !$this->is_admin();
        //no cookie?
        if (!$bl_disable_sid && $this->get_id() && $this->can_send_sid_with_request($bl_force_sid)) {
            $s_ret = ($bl_force_sid ? $this->get_forced_name() : $this->get_name()) . '=' . $this->get_id();
        }
        if ($this->is_admin()) {
            // admin mode always has to have token
            if ($s_ret) {
                $s_ret .= '&amp;';
            }
            $s_ret .= 'stoken=' . $this->get_session_challenge_token() . $this->get_shop_url_id();
        }
        return $s_ret;
    }
    /**
     * Forms input ("hidden" type) to pass session ID after submitting forms.
     *
     * @return string
     */
    public function hidden_sid()
    {
        $s_sid = $s_token = '';
        if ($this->is_sid_needed()) {
            $s_sid = '<input type="hidden" name="' . $this->get_name() . '" value="' . $this->get_id() . '" />';
        }
        if ($this->get_id()) {
            $s_token = '<input type="hidden" name="stoken" value="' . $this->get_session_challenge_token() . '" />';
        }
        return $s_token . $s_sid;
    }
    /**
     * Returns basket session object.
     *
     * @return \OxidEsales\Eshop\Application\Model\Basket
     */
    public function get_basket()
    {
        if ($this->_o_basket === null) {
            $serialized_basket = $this->get_variable($this->get_basket_name());
            //init oxbasketitem class first
            //#1746
            ox_new(Basket_Item::class);
            // init oxbasket through oxNew and not oxAutoload, Mantis-Bug #0004262
            $empty_basket = ox_new(Basket::class);
            $basket = $this->is_serialized_basket_valid($serialized_basket) && ($unserialized_basket = unserialize($serialized_basket)) && $this->is_unserialized_basket_valid($unserialized_basket, $empty_basket) ? $unserialized_basket : $empty_basket;
            $this->validate_basket($basket);
            $this->set_basket($basket);
        }
        return $this->_o_basket;
    }
    /**
     * True if given serialized object is constructed with compatible classes.
     *
     * @param string $serializedBasket
     * @return bool
     */
    protected function is_serialized_basket_valid($serialized_basket)
    {
        $basket_class = ox_new(Basket::class)::class;
        $basket_item_class = ox_new(Basket_Item::class)::class;
        $price_class = ox_new(\Oxid_Esales\Eshop\Core\Price::class)::class;
        $price_list_class = ox_new(\Oxid_Esales\Eshop\Core\Price_List::class)::class;
        $user_class = ox_new(User::class)::class;
        return $serialized_basket && $this->is_class_in_serialized_object($serialized_basket, $basket_class) && $this->is_class_in_serialized_object($serialized_basket, $basket_item_class) && $this->is_class_or_null_in_serialized_object_after_field($serialized_basket, 'oPrice', $price_class) && $this->is_class_or_null_in_serialized_object_after_field($serialized_basket, 'oProductsPriceList', $price_list_class) && $this->is_class_or_null_in_serialized_object_after_field($serialized_basket, 'oUser', $user_class);
    }
    /**
     * True if given class is found within serialized object.
     *
     * @param string $serializedObject
     * @param string $className
     *
     * @return bool
     */
    protected function is_class_in_serialized_object($serialized_object, $class_name)
    {
        $quoted_class_name = sprintf('"%s"', $class_name);
        return str_contains($serialized_object, $quoted_class_name);
    }
    /**
     * True if given class or null value is found after given field in serialized object.
     *
     * @param string $serializedObject
     * @param string $fieldName
     * @param string $className
     *
     * @return bool
     */
    protected function is_class_or_null_in_serialized_object_after_field($serialized_object, $field_name, $class_name)
    {
        $field_and_class_pattern = '/' . preg_quote($field_name, '/') . '";((?P<null>N);|O:\d+:"(?P<class>[\w\\\\]+)":)/';
        $match_found = preg_match($field_and_class_pattern, $serialized_object, $matches) === 1;
        return $match_found && (isset($matches['class']) && $matches['class'] === $class_name || isset($matches['null']) && $matches['null'] === 'N');
    }
    /**
     * True if both basket objects have been constructed from same class.
     *
     * Shop cannot function properly if provided with different basket class.
     *
     * @param \OxidEsales\Eshop\Application\Model\Basket $basket
     * @param \OxidEsales\Eshop\Application\Model\Basket $emptyBasket
     *
     * @return bool
     */
    protected function is_unserialized_basket_valid($basket, $empty_basket)
    {
        return $basket && $basket::class === $empty_basket::class;
    }
    /**
     * Validate loaded from session basket content. Check for language change.
     *
     * @param \OxidEsales\Eshop\Application\Model\Basket $oBasket Basket object loaded from session.
     */
    protected function validate_basket(\Oxid_Esales\Eshop\Application\Model\Basket $o_basket)
    {
        $a_curr_content = $o_basket->get_contents();
        if (empty($a_curr_content)) {
            return;
        }
        $i_curr_lang = Registry::get_lang()->get_base_language();
        foreach ($a_curr_content as $o_content) {
            if ($o_content->get_language_id() != $i_curr_lang) {
                $o_content->set_language_id($i_curr_lang);
            }
        }
    }
    /**
     * Sets basket session object.
     *
     * @param object $oBasket basket object
     */
    public function set_basket($o_basket): void
    {
        // sets basket session object
        $this->_o_basket = $o_basket;
    }
    /**
     * Deletes basket session object.
     */
    public function del_basket(): void
    {
        $this->set_basket(null);
        $this->delete_variable($this->get_basket_name());
    }
    /**
     * Indicates if setting of session id is executed in this script.
     *
     * @return bool
     */
    public function is_new_session()
    {
        return self::$_bl_is_new_session;
    }
    /**
     * Forces starting session and skips checking if session is allowed to start
     * when calling \OxidEsales\Eshop\Core\Session::start();
     */
    public function set_force_new_session(): void
    {
        $this->_bl_force_new_session = true;
    }
    /**
     * Checks if cookies are not available. Returns TRUE of sid needed
     *
     * @param string $sUrl if passed domain does not match current - returns true (optional)
     *
     * @return bool
     */
    public function is_sid_needed($s_url = null)
    {
        if ($this->is_admin()) {
            return true;
        }
        $o_config = Registry::get_config();
        if (!$this->get_session_use_cookies() || $s_url && $this->get_cookie_sid() && !$o_config->is_current_protocol($s_url)) {
            // switching from ssl to non ssl or vice versa?
            return true;
        }
        if ($s_url && !$o_config->is_current_url($s_url)) {
            return true;
        }
        if ($s_url && $o_config->is_current_url($s_url)) {
            return false;
        }
        if ($this->_bl_sid_needed === null) {
            // setting initial state
            $this->_bl_sid_needed = false;
            // no SIDs for search engines
            if (!Registry::get_utils()->is_search_engine()) {
                // cookie found - SID is not needed
                if (Registry::get_utils_server()->get_ox_cookie($this->get_name())) {
                    $this->_bl_sid_needed = false;
                } elseif ($this->force_session_start()) {
                    $this->_bl_sid_needed = true;
                } else if ($bl_sid_needed = $this->get_variable('blSidNeeded')) {
                    $this->_bl_sid_needed = true;
                } elseif ($this->is_session_required_action() && !count($_COOKIE)) {
                    $this->_bl_sid_needed = true;
                    // storing to session, performance..
                    $this->set_variable('blSidNeeded', $this->_bl_sid_needed);
                }
            }
        }
        return $this->_bl_sid_needed;
    }
    /**
     * Checks if current session id is the same as in originally received cookie.
     * This method is intended to indicate if new session cookie
     * is to be sent as header from this script execution.
     *
     * @return bool
     */
    public function is_actual_sid_in_cookie()
    {
        return isset($_COOKIE[$this->get_name()]) && $_COOKIE[$this->get_name()] == $this->get_id();
    }
    /**
     * Appends url with session ID, but only if \OxidEsales\Eshop\Core\Session::_isSidNeeded() returns true
     * Direct usage of this method to retrieve end url result is discouraged - instead
     * see \OxidEsales\Eshop\Core\UtilsUrl::processUrl
     *
     * @param string $sUrl url to append with sid
     *
     * @see \OxidEsales\Eshop\Core\UtilsUrl::processUrl
     *
     * @return string
     */
    public function process_url($s_url)
    {
        if ($this->is_sid_needed($s_url)) {
            $s_sid = $this->sid(true);
            if ($s_sid) {
                $this->sid_to_url_event();
                $o_str = Str::get_str();
                $a_url_parts = explode('#', $s_url);
                if (!$o_str->preg_match('/(\?|&(amp;)?)sid=/i', $a_url_parts[0]) && false === $o_str->strpos($a_url_parts[0], $s_sid)) {
                    if (!$o_str->preg_match('/(\?|&(amp;)?)$/', $s_url)) {
                        $a_url_parts[0] .= $o_str->strstr($a_url_parts[0], '?') !== false ? '&amp;' : '?';
                    }
                    $a_url_parts[0] .= $s_sid . '&amp;';
                }
                $s_url = join('#', $a_url_parts);
            }
        }
        return $s_url;
    }
    /**
     * Returns remote access key. With this key (called over "remotekey" URL parameter) and session id (sid parameter) you can access
     * session from another client.
     * The key is generated once per session after the first request.
     *
     * @param bool $blGenerateNew Should new token be generated
     *
     * @return string
     */
    public function get_remote_access_token($bl_generate_new = true)
    {
        $s_token = $this->get_variable('_rtoken');
        if (!$s_token && $bl_generate_new) {
            $s_token = md5(random_int(0, mt_getrandmax()) . $this->get_id());
            $s_token = substr($s_token, 0, 8);
            $this->set_variable('_rtoken', $s_token);
        }
        return $s_token;
    }
    /**
     * Returns true if its not search engine and config option blForceSessionStart = 1/true
     * or _GET parameter "su" (suggested user id) is set.
     *
     * @return bool
     */
    protected function force_session_start()
    {
        return !Registry::get_utils()->is_search_engine() && (Container_Facade::get_parameter('oxid_esales.force_session_start') || Registry::get_request()->get_request_escaped_parameter('su') || $this->_bl_force_new_session);
    }
    /**
     * Checks if we can start new session. Returns bool success status
     *
     * @return bool
     */
    protected function allow_session_start()
    {
        $bl_allow_session_start = true;
        $my_config = Registry::get_config();
        // special handling only in non-admin mode
        if (!$this->is_admin()) {
            if (Registry::get_utils()->is_search_engine() || Registry::get_request()->get_request_escaped_parameter('skipSession')) {
                $bl_allow_session_start = false;
            } elseif (Registry::get_utils_server()->get_ox_cookie('oxid_' . $my_config->get_shop_id() . '_autologin') === '1') {
                $bl_allow_session_start = true;
            } elseif (!$this->force_session_start() && !Registry::get_utils_server()->get_ox_cookie('sid_key')) {
                // session is not needed to start when it is not necessary:
                // - no sid in request and also user executes no session connected action
                // - no cookie set and user executes no session connected action
                if (!Registry::get_utils_server()->get_ox_cookie($this->get_name()) && !$this->can_take_sid_from_request() && !$this->is_session_required_action()) {
                    $bl_allow_session_start = false;
                }
            }
        }
        return $bl_allow_session_start;
    }
    /**
     * Saves various visitor parameters and compares with current data.
     * Returns true if any change is detected.
     * Using this method we can detect different visitor with same session id.
     *
     * @return bool
     */
    protected function is_swapped_client()
    {
        $bl_swapped = false;
        $my_utils_server = Registry::get_utils_server();
        // check only for non search engines
        if (!Registry::get_utils()->is_search_engine() && !$my_utils_server->is_trusted_client_ip() && !$this->is_valid_remote_access_token()) {
            $my_config = Registry::get_config();
            // checking if session user agent matches actual
            $bl_swapped = $this->check_user_agent($my_utils_server->get_server_var('HTTP_USER_AGENT'), $this->get_variable('sessionagent'));
            if (!$bl_swapped) {
                $bl_disable_cookie_check = $my_config->get_config_param('blDisableCookieCheck');
                $bl_use_cookies = $this->get_session_use_cookies();
                if (!$bl_disable_cookie_check && $bl_use_cookies) {
                    $bl_swapped = $this->check_cookies($my_utils_server->get_ox_cookie('sid_key'), $this->get_variable('sessioncookieisset'));
                }
            }
        }
        return $bl_swapped;
    }
    /**
     * Checking user agent
     *
     * @param string $sAgent         current user agent
     * @param string $sExistingAgent existing user agent
     *
     * @return bool
     */
    protected function check_user_agent($s_agent, $s_existing_agent)
    {
        $bl_check = false;
        // processing
        $o_utils = Registry::get_utils_server();
        $s_agent = $o_utils->process_user_agent_info($s_agent);
        $s_existing_agent = $o_utils->process_user_agent_info($s_existing_agent);
        if ($s_agent && $s_agent !== $s_existing_agent) {
            if ($s_existing_agent) {
                $this->_s_error_msg = "Different browser ({$s_existing_agent}, {$s_agent}), creating new SID...<br>";
            }
            $bl_check = true;
        }
        return $bl_check;
    }
    /**
     * Check for existing cookie.
     * Cookie info is dropped from time to time.
     *
     * @param string $sCookieSid         coockie sid
     * @param array  $aSessCookieSetOnce if session cookie is set
     *
     * @return bool
     */
    protected function check_cookies($s_cookie_sid, $a_sess_cookie_set_once)
    {
        $bl_swapped = false;
        $my_config = Registry::get_config();
        $curr_url = $my_config->get_shop_url();
        $bl_sess_cookie_set_once = false;
        if (is_array($a_sess_cookie_set_once) && isset($a_sess_cookie_set_once[$curr_url])) {
            $bl_sess_cookie_set_once = $a_sess_cookie_set_once[$curr_url];
        }
        //if cookie was there once but now is gone it means we have to reset
        if ($bl_sess_cookie_set_once && !$s_cookie_sid) {
            if (Container_Facade::get_parameter('oxid_esales.debug_mode')) {
                $this->_s_error_msg = 'Cookie not found, creating new SID...<br>';
                $this->_s_error_msg .= "Cookie: {$s_cookie_sid}<br>";
                $this->_s_error_msg .= "Session: {$bl_sess_cookie_set_once}<br>";
                $this->_s_error_msg .= 'URL: ' . $curr_url . '<br>';
            }
            $bl_swapped = true;
        }
        //if we detect the cookie then set session var for possible later use
        if ($s_cookie_sid == 'oxid' && !$bl_sess_cookie_set_once) {
            if (!is_array($a_sess_cookie_set_once)) {
                $a_sess_cookie_set_once = [];
            }
            $a_sess_cookie_set_once[$curr_url] = 'ox_true';
            $this->set_variable('sessioncookieisset', $a_sess_cookie_set_once);
        }
        //if we have no cookie then try to set it
        if (!$s_cookie_sid) {
            Registry::get_utils_server()->set_ox_cookie('sid_key', 'oxid');
        }
        return $bl_swapped;
    }
    /**
     * Sests session id to $sSessId
     *
     * @param string $sSessId sesion ID
     */
    protected function set_session_id($s_sess_id)
    {
        //marking this session as new one, as it might be not writen to db yet
        if ($s_sess_id && session_id() != $s_sess_id) {
            $this->_bl_new_session = true;
        }
        session_id($s_sess_id);
        $this->set_id($s_sess_id);
        $this->set_session_cookie($s_sess_id);
    }
    /**
     * Returns name of shopping basket.
     *
     * @return string
     */
    protected function get_basket_name()
    {
        return 'basket';
    }
    /**
     * Returns cookie sid value
     *
     * @return string
     */
    protected function get_cookie_sid()
    {
        return Registry::get_utils_server()->get_ox_cookie($this->get_name());
    }
    /**
     * returns configuration array with info which parameters require session
     * start
     *
     * @return array
     */
    protected function get_require_session_with_params()
    {
        $config = Container_Facade::get_parameter('oxid_esales.session_init_params');
        $defaults = $this->_a_require_session_with_params;
        if (!$config) {
            return $defaults;
        }
        foreach ($config as $key => $val) {
            if ($val && !\is_array($val)) {
                unset($defaults[$key]);
            }
        }
        return array_replace_recursive($defaults, $config);
    }
    /**
     * Tests if current action requires session
     *
     * @return bool
     */
    protected function is_session_required_action()
    {
        foreach ($this->get_require_session_with_params() as $s_param => $a_values) {
            $s_value = Registry::get_request()->get_request_escaped_parameter($s_param);
            if (isset($s_value)) {
                if (is_array($a_values)) {
                    if (isset($a_values[$s_value]) && $a_values[$s_value]) {
                        return true;
                    }
                } elseif ($a_values) {
                    return true;
                }
            }
        }
        return isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] == 'POST';
    }
    /**
     * return cookies usage for sid possibilities
     *
     * @return bool
     */
    protected function get_session_use_cookies()
    {
        if ($this->is_admin()) {
            return true;
        }
        return (bool) Container_Facade::get_parameter('oxid_esales.cookies_session');
    }
    /**
     * Checks if token supplied over 'rtoken' parameter matches remote access session token.
     *
     * @return bool
     */
    protected function is_valid_remote_access_token()
    {
        $input_token = Registry::get_request()->get_request_escaped_parameter('rtoken');
        $token = $this->get_remote_access_token(false);
        return !empty($input_token) && $token === $input_token;
    }
    /**
     * return basket reservations handler object
     *
     * @return \OxidEsales\Eshop\Application\Model\BasketReservation
     */
    public function get_basket_reservations()
    {
        if (!$this->_o_basket_reservations) {
            $this->_o_basket_reservations = ox_new(\Oxid_Esales\Eshop\Application\Model\Basket_Reservation::class);
        }
        return $this->_o_basket_reservations;
    }
    /**
     * Checks if headers were already outputed
     *
     * @return bool
     */
    public function is_header_sent()
    {
        return headers_sent();
    }
    /**
     * Returns true if session was started
     *
     * @return bool
     */
    public function is_session_started()
    {
        return session_status() === PHP_SESSION_ACTIVE;
    }
    /**
     * Return Shop IR parameter for Url.
     *
     * @return string
     */
    protected function get_shop_url_id()
    {
        return '';
    }
    /**
     * Decide if need to set session headers to browser.
     *
     * @return bool
     */
    protected function need_to_set_headers()
    {
        return true;
    }
    /**
     * Place to hook when SID is added to URL.
     */
    protected function sid_to_url_event()
    {
    }
    /**
     * Set session cookie
     *
     * @param string $sessionId   Session cookie value
     */
    protected function set_session_cookie($session_id): void
    {
        if ($this->get_session_use_cookies()) {
            if (!$this->allow_session_start()) {
                Registry::get_utils_server()->set_ox_cookie($this->get_name(), null);
            } else {
                Registry::get_utils_server()->set_ox_cookie($this->get_name(), $session_id);
            }
        }
    }
    private function is_force_sid_blocked(): bool
    {
        return Container_Facade::get_parameter('oxid_esales.disallow_force_session_id');
    }
    private function can_send_sid_with_request(bool $use_force_sid): bool
    {
        return ($use_force_sid || !$this->get_session_use_cookies() || !$this->get_cookie_sid()) && !($use_force_sid && $this->is_force_sid_blocked());
    }
    private function can_take_sid_from_request(): bool
    {
        if (Registry::get_request()->get_request_escaped_parameter($this->get_name())) {
            return true;
        }
        return Registry::get_request()->get_request_escaped_parameter($this->get_forced_name()) && !$this->is_force_sid_blocked();
    }
}