<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Core;

use function is_array;
use Oxid_Esales\Eshop\Core\Registry;
use Oxid_Esales\Eshop\Core\Str;
use Oxid_Esales\Eshop\Core\Table_View_Name_Generator;
use Oxid_Esales\Eshop_Community\Core\Di\Container_Facade;
use Oxid_Esales\Eshop_Community\Internal\Transition\Shop_Events\Application_Exit_Event;
use Psr\Cache\Cache_Item_Pool_Interface;
use stdClass;
use Symfony\Contracts\Cache\Item_Interface;
use Symfony\Contracts\Cache\Tag_Aware_Cache_Interface;
/**
 * General utils class
 */
class Utils extends \Oxid_Esales\Eshop\Core\Base
{
    /**
     * Cached currency precision
     *
     * @var int
     */
    protected $_i_cur_precision;
    /**
     * Some files, like object structure should not be deleted, because they are changed rarely
     * and each regeneration eats additional page load time. This array keeps patterns of file
     * names which should not be deleted on regular cache cleanup
     *
     * @var string
     */
    protected $_s_permanent_cache_pattern = '/c_fieldnames_|c_tbdsc_|_allfields_/';
    /**
     * Pattern used to filter needed to remove language cache files.
     *
     * @var string
     */
    protected $_s_language_cache_pattern = '/c_langcache_/i';
    /**
     * Pattern used to filter needed to remove admin menu cache files.
     *
     * @var string
     */
    protected $_s_menu_cache_pattern = '/c_menu_/i';
    /**
     * File cache contents.
     *
     * @var array
     */
    protected $_a_locked_file_handles = [];
    /**
     * Local cache
     *
     * @var array
     */
    protected $_a_file_cache_contents = [];
    /**
     * Search engine indicator
     *
     * @var bool
     */
    protected $_bl_is_se;
    /**
     * Statically cached data
     *
     * @var array
     */
    protected $_a_static_cache;
    /**
     * Seo mode marker - SEO is active or not
     *
     * @var bool
     */
    protected $_bl_seo_is_active;
    /**
     * Returns string witch "." symbols were replaced with "__".
     *
     * @param string $sName String to search replaceable char
     *
     * @return string
     */
    public function get_arr_fld_name($s_name)
    {
        return str_replace('.', '__', $s_name);
    }
    /**
     * Takes a string and assign all values, returns array with values.
     *
     * @param string $sIn  Initial string
     * @param float  $dVat Article VAT (optional)
     *
     * @return array
     */
    public function assign_values_from_text($s_in, $d_vat = null)
    {
        $a_ret = [];
        $a_pieces = explode('@@', $s_in);
        foreach ($a_pieces as $s_val) {
            if ($s_val) {
                $a_name = explode('__', $s_val);
                if (isset($a_name[0]) && isset($a_name[1])) {
                    $a_ret[] = $this->fill_explode_array($a_name, $d_vat);
                }
            }
        }
        return $a_ret;
    }
    /**
     * Takes an array and builds again a string. Returns string with values.
     *
     * @param array $aIn Initial array of strings
     *
     * @return string
     */
    public function assign_values_to_text($a_in)
    {
        $s_ret = '';
        reset($a_in);
        foreach ($a_in as $s_key => $s_val) {
            $s_ret .= $s_key;
            $s_ret .= '__';
            $s_ret .= $s_val;
            $s_ret .= '@@';
        }
        return $s_ret;
    }
    /**
     * Returns formatted currency string, according to formatting standards.
     *
     * @param string $sValue Formatted price
     *
     * @return float
     */
    public function currency2Float($s_value)
    {
        $f_ret = $s_value;
        $i_pos = strrpos($s_value, '.');
        if ($i_pos && strlen($s_value) - 1 - $i_pos < 2 + 1) {
            // replace decimal with ","
            $f_ret = substr_replace($f_ret, ',', $i_pos, 1);
        }
        // remove thousands
        $f_ret = str_replace([' ', '.'], '', $f_ret);
        return (float) str_replace(',', '.', $f_ret);
    }
    /**
     * Returns formatted float, according to formatting standards.
     *
     * @param string $sValue Formatted price
     *
     * @return float
     */
    public function string2Float($s_value)
    {
        $f_ret = str_replace(' ', '', $s_value);
        $i_comma_pos = strpos($f_ret, ',');
        $i_dot_pos = strpos($f_ret, '.');
        if (!$i_dot_pos xor !$i_comma_pos) {
            if (substr_count($f_ret, ',') > 1 || substr_count($f_ret, '.') > 1) {
                $f_ret = str_replace([',', '.'], '', $f_ret);
            } else {
                $f_ret = str_replace(',', '.', $f_ret);
            }
        } else if ($i_dot_pos < $i_comma_pos) {
            $f_ret = str_replace('.', '', $f_ret);
            $f_ret = str_replace(',', '.', $f_ret);
        }
        // remove thousands
        return (float) str_replace([' ', ','], '', $f_ret);
    }
    /**
     * Checks if current web client is Search Engine. Returns true on success.
     *
     * @param string $sClient user browser agent
     *
     * @return bool
     */
    public function is_search_engine($s_client = null)
    {
        if (is_null($this->_bl_is_se)) {
            $this->set_search_engine(null, $s_client);
        }
        return $this->_bl_is_se;
    }
    /**
     * Sets if current web client is Search Engine.
     *
     * @param bool $isSearchEngine sets if Search Engine is on
     * @param string $userAgent user browser agent
     */
    public function set_search_engine($is_search_engine = null, $user_agent = null): void
    {
        if (isset($is_search_engine)) {
            $this->_bl_is_se = $is_search_engine;
            return;
        }
        start_profile('isSearchEngine');
        $is_search_engine = false;
        if (!(Container_Facade::get_parameter('oxid_esales.debug_mode') && $this->is_admin())) {
            $robots = Container_Facade::get_parameter('oxid_esales.search_engine_list');
            $robots = \is_array($robots) ? $robots : [];
            $user_agent = $user_agent ?: strtolower(getenv('HTTP_USER_AGENT'));
            foreach ($robots as $robot) {
                if (str_contains($user_agent, (string) $robot)) {
                    $is_search_engine = true;
                    break;
                }
            }
        }
        $this->_bl_is_se = $is_search_engine;
        stop_profile('isSearchEngine');
    }
    /**
     * Parses profile configuration, loads stored info in cookie
     *
     * @param array $aInterfaceProfiles ($myConfig->getConfigParam( 'aInterfaceProfiles' ))
     */
    public function load_admin_profile($a_interface_profiles)
    {
        // improved #533
        // checking for available profiles list
        if (is_array($a_interface_profiles)) {
            //checking for previous profiles
            $s_prev_profile = Registry::get_utils_server()->get_ox_cookie('oxidadminprofile');
            if (isset($s_prev_profile)) {
                $a_prev_profile = @explode('@', trim($s_prev_profile));
            }
            //array to store profiles
            $a_profiles = [];
            foreach ($a_interface_profiles as $i_pos => $s_profile) {
                $a_profile_settings = [$i_pos, $s_profile];
                $a_profiles[] = $a_profile_settings;
            }
            // setting previous used profile as active
            if (isset($a_prev_profile[0]) && isset($a_profiles[$a_prev_profile[0]])) {
                $a_profiles[$a_prev_profile[0]][2] = 1;
            }
            Registry::get_session()->set_variable('aAdminProfiles', $a_profiles);
            return $a_profiles;
        }
        return null;
    }
    /**
     * Rounds the value to currency cents. This method does NOT format the number.
     *
     * @param string $value the value that should be rounded
     * @param object $currency
     *
     * @return float
     */
    public function f_round($value, $currency = null)
    {
        start_profile('fround');
        //cached currency precision, this saves about 1% of execution time
        if (is_null($this->_i_cur_precision)) {
            $currency = $currency ?: Registry::get_config()->get_act_shop_currency_object();
            $this->_i_cur_precision = $currency->decimal;
        }
        $rounded_value = round((float) $value, $this->_i_cur_precision);
        stop_profile('fround');
        return $rounded_value;
    }
    /**
     * Alphanumeric oxid and pure numeric oxid that start with the numeric part and only differ
     * in postfixed alphabetical characters (e.g. "123" and "123X") are cast to the wrong type
     * php internally which might result in wrong array_search results.
     *
     * Wrapper for php internal array_search function, ony usable for string search.
     * In case we get unclear results make sure we typecast all data
     * to string before performing array search.
     *
     * @param string $needle
     * @param array  $haystack
     *
     * @return mixed
     */
    public function array_string_search($needle, $haystack)
    {
        $result = array_search((string) $needle, $haystack);
        $second = array_search((string) $needle, $haystack, true);
        //got a different result when using strict and not strict?
        //do a detail check
        if ($result != $second) {
            $stringstack = [];
            foreach ($haystack as $value) {
                $stringstack[] = (string) $value;
            }
            $result = array_search((string) $needle, $stringstack, true);
        }
        return $result;
    }
    /**
     * Stores something into static cache to avoid double loading
     *
     * @param string $sName    name of the content
     * @param mixed  $sContent the content
     * @param string $sKey     optional key, where to store the content
     */
    public function to_static_cache($s_name, $s_content, $s_key = null): void
    {
        // if it's an array then we add
        if ($s_key) {
            $this->_a_static_cache[$s_name][$s_key] = $s_content;
        } else {
            $this->_a_static_cache[$s_name] = $s_content;
        }
    }
    /**
     * Retrieves something from static cache
     *
     * @param string $sName name under which the content is stored in the static cache
     *
     * @return mixed
     */
    public function from_static_cache($s_name)
    {
        if (isset($this->_a_static_cache[$s_name])) {
            return $this->_a_static_cache[$s_name];
        }
    }
    /**
     * Cleans all or specific data from static cache
     *
     * @param string $sCacheName Cache name
     */
    public function clean_static_cache($s_cache_name = null): void
    {
        if ($s_cache_name) {
            unset($this->_a_static_cache[$s_cache_name]);
        } else {
            $this->_a_static_cache = null;
        }
    }
    /**
     * @deprecated will be removed in next major version
     *
     * Adds contents to cache contents by given key. Returns true on success.
     *
     * @param string $sKey      Cache key
     * @param mixed  $mContents Contents to cache
     * @param int    $iTtl      Time to live in seconds (0 for forever).
     *
     * @return bool
     */
    public function to_file_cache($s_key, $m_contents, $i_ttl = 0)
    {
        $cache = Container_Facade::get(Cache_Item_Pool_Interface::class);
        $cache_item = $cache->get_item($s_key)->set($m_contents);
        if ($i_ttl) {
            $cache_item->expires_after($i_ttl);
        }
        $cache->save($cache_item);
        return true;
    }
    /**
     * @deprecated will be removed in next major version
     *
     * Fetches contents from file cache.
     *
     * @param string $sKey Cache key
     *
     * @return mixed
     */
    public function from_file_cache($s_key)
    {
        $cache = Container_Facade::get(Cache_Item_Pool_Interface::class);
        if ($cache->has_item($s_key)) {
            $cache_item = $cache->get_item($s_key);
            return $cache_item->get();
        }
        return null;
    }
    /**
     * @deprecated will be removed in next major version
     */
    public function ox_reset_file_cache(): void
    {
        $cache = Container_Facade::get(Cache_Item_Pool_Interface::class);
        $cache->clear();
    }
    /**
     * @deprecated will be removed in next major version
     *
     * Removes language constant cache
     */
    public function reset_language_cache(): void
    {
        $cache = Container_Facade::get(Tag_Aware_Cache_Interface::class);
        $cache->invalidate_tags(['oxid_esales.cache.language']);
    }
    /**
     * @deprecated will be removed in next major version
     *
     * Removes admin menu cache
     */
    public function reset_menu_cache(): void
    {
        $cache = Container_Facade::get(Tag_Aware_Cache_Interface::class);
        $cache->invalidate_tags(['oxid_esales.cache.menu']);
    }
    /**
     * Checks if preview mode is ON
     *
     * @return bool
     */
    public function can_preview()
    {
        $bl_can = null;
        if (($s_prev_id = Registry::get_request()->get_request_escaped_parameter('preview')) && $s_admin_sid = Registry::get_utils_server()->get_ox_cookie('admin_sid')) {
            $table_view_name_generator = ox_new(Table_View_Name_Generator::class);
            $s_table = $table_view_name_generator->get_view_name('oxuser');
            $s_q = "SELECT 1 FROM {$s_table} WHERE MD5( CONCAT( :adminsid, {$s_table}.oxid, {$s_table}.oxpassword, {$s_table}.oxrights ) ) = :previd";
            $bl_can = (bool) \Oxid_Esales\Eshop\Core\Database_Provider::get_db()->get_one($s_q, ['adminsid' => $s_admin_sid, 'previd' => $s_prev_id]);
        }
        return $bl_can;
    }
    /**
     * Returns id which is used for product preview in shop during administration
     *
     * @return string
     */
    public function get_preview_id()
    {
        $s_admin_sid = Registry::get_utils_server()->get_ox_cookie('admin_sid');
        if ($o_user = $this->get_user()) {
            return md5($s_admin_sid . $o_user->get_id() . $o_user->oxuser__oxpassword->value . $o_user->oxuser__oxrights->value);
        }
    }
    /**
     * This function checks if logged in user has access to admin or not
     *
     * @return bool
     */
    public function check_access_rights()
    {
        $my_config = Registry::get_config();
        $bl_is_auth = false;
        $s_user_id = Registry::get_session()->get_variable('auth');
        // deleting admin marker
        Registry::get_session()->set_variable('malladmin', 0);
        Registry::get_session()->set_variable('blIsAdmin', 0);
        Registry::get_session()->delete_variable('blIsAdmin');
        $my_config->set_config_param('blMallAdmin', false);
        //#1552T
        $my_config->set_config_param('blAllowInheritedEdit', false);
        if ($s_user_id) {
            // escaping
            $s_rights = $this->fetch_rights_for_user($s_user_id);
            if ($s_rights != 'user') {
                // malladmin ?
                if ($s_rights == 'malladmin') {
                    Registry::get_session()->set_variable('malladmin', 1);
                    $my_config->set_config_param('blMallAdmin', true);
                    //#1552T
                    //So far this blAllowSharedEdit is Equal to blMallAdmin but in future to be solved over rights and roles
                    $my_config->set_config_param('blAllowSharedEdit', true);
                    $s_shop = Registry::get_session()->get_variable('actshop');
                    if (!isset($s_shop)) {
                        Registry::get_session()->set_variable('actshop', $my_config->get_base_shop_id());
                    }
                    $bl_is_auth = true;
                } else {
                    // Shopadmin... check if this shop is valid and exists
                    $s_shop_id = $this->fetch_shop_admin_by_id($s_rights);
                    if (isset($s_shop_id) && $s_shop_id) {
                        // success, this shop exists
                        Registry::get_session()->set_variable('actshop', $s_rights);
                        Registry::get_session()->set_variable('currentadminshop', $s_rights);
                        Registry::get_session()->set_variable('shp', $s_rights);
                        // check if this subshop admin is evil.
                        if ('chshp' == Registry::get_request()->get_request_escaped_parameter('fnc')) {
                            // dont allow this call
                            $bl_is_auth = false;
                        } else {
                            $bl_is_auth = true;
                            $a_shop_id_vars = ['actshop', 'shp', 'currentadminshop'];
                            foreach ($a_shop_id_vars as $s_shop_id_var) {
                                if (!$s_got_shop = Registry::get_request()->get_request_escaped_parameter($s_shop_id_var)) {
                                    continue;
                                }
                                if ($s_got_shop == $s_rights) {
                                    continue;
                                }
                                $bl_is_auth = false;
                                break;
                            }
                        }
                    }
                }
                // marking user as admin
                Registry::get_session()->set_variable('blIsAdmin', 1);
            }
        }
        return $bl_is_auth;
    }
    /**
     * Fetch the rights for the user given by its oxid
     *
     * @param string $userOxId The oxId of the user we want the rights for.
     *
     * @return mixed The rights
     */
    protected function fetch_rights_for_user($user_ox_id)
    {
        $database = \Oxid_Esales\Eshop\Core\Database_Provider::get_db();
        return $database->get_one('SELECT oxrights FROM oxuser WHERE oxid = :oxid ', ['oxid' => $user_ox_id]);
    }
    /**
     * Fetch the oxId from the oxshops table.
     *
     * @param string $oxId The oxId of the shop.
     *
     * @return mixed The oxId of the shop with the given oxId.
     */
    protected function fetch_shop_admin_by_id($ox_id)
    {
        $database = \Oxid_Esales\Eshop\Core\Database_Provider::get_db();
        return $database->get_one('SELECT oxid FROM oxshops WHERE oxid = :oxid', ['oxid' => $ox_id]);
    }
    /**
     * Checks if Seo mode should be used
     *
     * @param bool $reset used to reset cached SEO mode
     * @param string $shopId shop id (optional; if not passed active session shop id will be used)
     * @param int $languageId language id (optional; if not passed active session language will be used)
     *
     * @return bool
     */
    public function seo_is_active($reset = false, $shop_id = null, $language_id = null)
    {
        if (!isset($this->_bl_seo_is_active) || $reset) {
            $this->_bl_seo_is_active = $this->is_seo_enabled() && !$this->is_seo_disabled_for_shop_and_language((int) $shop_id ?: Registry::get_config()->get_active_shop()->get_id(), (int) $language_id ?: (int) Registry::get_lang()->get_base_language());
        }
        return $this->_bl_seo_is_active;
    }
    /**
     * Checks if string is only alpha numeric  symbols
     *
     * @param string $sField field name to test
     *
     * @return bool
     */
    public function is_valid_alpha($s_field)
    {
        return (bool) Str::get_str()->preg_match('/^[a-zA-Z0-9_]*$/', $s_field);
    }
    /**
     * redirects browser to given url, nothing else done just header send
     * may be used for redirection in case of an exception or similar things
     *
     * @param string $sUrl        the URL to redirect to
     * @param string $sHeaderCode code to add to the header(e.g. "HTTP/1.1 301 Moved Permanently", or "HTTP/1.1 500 Internal Server Error"
     */
    protected function simple_redirect($s_url, $s_header_code)
    {
        $o_header = ox_new(\Oxid_Esales\Eshop\Core\Header::class);
        $o_header->set_header($s_header_code);
        $o_header->set_header("Location: {$s_url}");
        $o_header->set_header('Connection: close');
        $o_header->send_header();
    }
    /**
     * Shows offline page.
     * Directly displays the offline page to the client (browser)
     * with a 500 status code header.
     */
    public function show_offline_page(): void
    {
        ox_trigger_offline_page_display();
        $this->show_message_and_exit('');
    }
    /**
     * redirect user to the specified URL
     *
     * @param string $sUrl               URL to be redirected
     * @param bool   $blAddRedirectParam add "redirect" param
     * @param int    $iHeaderCode        header code, default 302
     *
     * @return null or exit
     */
    public function redirect($s_url, $bl_add_redirect_param = true, $i_header_code = 302): void
    {
        //preventing possible cyclic redirection
        //#M341 and check only if redirect parameter must be added
        if ($bl_add_redirect_param && Registry::get_request()->get_request_escaped_parameter('redirected')) {
            return;
        }
        if ($bl_add_redirect_param) {
            $s_url = $this->add_url_parameters($s_url, ['redirected' => 1]);
        }
        $s_url = str_ireplace('&amp;', '&', $s_url);
        $s_header_code = match ($i_header_code) {
            301 => 'HTTP/1.1 301 Moved Permanently',
            500 => 'HTTP/1.1 500 Internal Server Error',
            default => 'HTTP/1.1 302 Found',
        };
        $this->simple_redirect($s_url, $s_header_code);
        try {
            //may occur in case db is lost
            $session = Registry::get_session();
            $session->freeze();
        } catch (\Oxid_Esales\Eshop\Core\Exception\Standard_Exception $exception) {
            Registry::get_logger()->error($exception->get_message(), [$exception]);
            //do nothing else to make sure the redirect takes place
        }
        $this->show_message_and_exit('');
    }
    /**
     * shows given message and quits
     * message might be whole content like 404 page.
     *
     * @param string $sMsg message to show
     */
    public function show_message_and_exit($s_msg): void
    {
        $this->prepare_to_exit();
        exit($s_msg);
    }
    /**
     * helper with commands to run before exit action
     */
    protected function prepare_to_exit()
    {
        $session = Registry::get_session();
        $session->freeze();
        Container_Facade::dispatch(new Application_Exit_Event());
        if ($this->is_search_engine()) {
            $header = Registry::get(\Oxid_Esales\Eshop\Core\Header::class);
            $header->set_non_cacheable();
        }
        //Send headers that have been registered
        $header = Registry::get(\Oxid_Esales\Eshop\Core\Header::class);
        $header->send_header();
    }
    /**
     * set header sent to browser
     *
     * @param string $sHeader header to sent
     */
    public function set_header($s_header): void
    {
        header($s_header);
    }
    /**
     * adds the given parameters at the end of the given url
     *
     * @param string $sUrl    a url
     * @param array  $aParams the params which will be added
     *
     * @return string
     */
    protected function add_url_parameters($s_url, $a_params)
    {
        $s_delimiter = Str::get_str()->strpos($s_url, '?') !== false ? '&' : '?';
        foreach ($a_params as $s_name => $s_val) {
            $s_url = $s_url . $s_delimiter . $s_name . '=' . $s_val;
            $s_delimiter = '&';
        }
        return $s_url;
    }
    /**
     * Fill array.
     *
     * @param array $aName Initial array of strings
     * @param float $dVat  Article VAT
     *
     * @return string
     *
     * @todo rename function more closely to actual purpose
     * @todo finish refactoring
     */
    protected function fill_explode_array($a_name, $d_vat = null)
    {
        $my_config = Registry::get_config();
        $o_object = new stdClass();
        $a_price = explode('!P!', (string) $a_name[0]);
        if ($my_config->get_config_param('bl_perfLoadSelectLists') && $my_config->get_config_param('bl_perfUseSelectlistPrice') && isset($a_price[0]) && isset($a_price[1]) || $this->is_admin()) {
            // yes, price is there
            $o_object->price = $a_price[1] ?? 0;
            $a_name[0] = $a_price[0] ?? '';
            $i_perc_pos = Str::get_str()->strpos($o_object->price, '%');
            if ($i_perc_pos !== false) {
                $o_object->price_unit = '%';
                $o_object->fprice = $o_object->price;
                $o_object->price = substr((string) $o_object->price, 0, $i_perc_pos);
            } else {
                $o_cur = $my_config->get_act_shop_currency_object();
                $o_object->price = str_replace(',', '.', $o_object->price);
                $o_object->fprice = Registry::get_lang()->format_currency($o_object->price * $o_cur->rate, $o_cur);
                $o_object->price_unit = 'abs';
            }
            // add price info into list
            if (!$this->is_admin() && $o_object->price != 0) {
                $a_name[0] .= ' ';
                $d_price = $this->prepare_price($o_object->price, $d_vat);
                if ($o_object->price > 0) {
                    $a_name[0] .= '+';
                }
                //V FS#2616
                if ($d_vat != null && $o_object->price_unit == 'abs') {
                    $o_price = ox_new(\Oxid_Esales\Eshop\Core\Price::class);
                    $o_price->set_price($o_object->price, $d_vat);
                    $a_name[0] .= Registry::get_lang()->format_currency($d_price * $o_cur->rate, $o_cur);
                } else {
                    $a_name[0] .= $o_object->fprice;
                }
                if ($o_object->price_unit == 'abs') {
                    $a_name[0] .= ' ' . $o_cur->sign;
                }
            }
        } elseif (isset($a_price[0]) && isset($a_price[1])) {
            // A. removing unused part of information
            $a_name[0] = Str::get_str()->preg_replace('/!P!.*/', '', $a_name[0]);
        }
        $o_object->name = $a_name[0];
        $o_object->value = $a_name[1];
        return $o_object;
    }
    /**
     * Prepares price depending what options are used(show as net, brutto, etc.) for displaying
     *
     * @param double $dPrice Price
     * @param double $dVat   VAT
     *
     * @return float
     */
    protected function prepare_price($d_price, $d_vat)
    {
        $bl_calculation_mode_netto = $this->is_price_view_mode_netto();
        $o_currency = Registry::get_config()->get_act_shop_currency_object();
        $bl_enter_net_price = Registry::get_config()->get_config_param('blEnterNetPrice');
        if ($bl_calculation_mode_netto && !$bl_enter_net_price) {
            $d_price = round(\Oxid_Esales\Eshop\Core\Price::brutto2Netto($d_price, $d_vat), $o_currency->decimal);
        } elseif (!$bl_calculation_mode_netto && $bl_enter_net_price) {
            $d_price = round(\Oxid_Esales\Eshop\Core\Price::netto2Brutto($d_price, $d_vat), $o_currency->decimal);
        }
        return $d_price;
    }
    /**
     * Checks and return true if price view mode is netto.
     *
     * @return bool
     */
    protected function is_price_view_mode_netto()
    {
        $bl_result = (bool) Registry::get_config()->get_config_param('blShowNetPrice');
        $o_user = $this->get_article_user();
        if ($o_user) {
            return $o_user->is_price_view_mode_netto();
        }
        return $bl_result;
    }
    /**
     * Return article user.
     *
     * @return \OxidEsales\Eshop\Application\Model\User
     */
    protected function get_article_user()
    {
        if (isset($this->_o_user) && $this->_o_user) {
            return $this->_o_user;
        }
        return $this->get_user();
    }
    /**
     * returns manually set mime types
     *
     * @param string $sFileName the file
     *
     * @return string
     */
    public function ox_mime_content_type($s_file_name)
    {
        $s_file_name = strtolower($s_file_name);
        $i_last_dot = strrpos($s_file_name, '.');
        $s_type = false;
        if ($i_last_dot !== false) {
            $s_type = substr($s_file_name, $i_last_dot + 1);
            $s_type = match ($s_type) {
                'gif' => 'image/gif',
                'jpeg', 'jpg' => 'image/jpeg',
                'png' => 'image/png',
                'webp' => 'image/webp',
                default => false,
            };
        }
        return $s_type;
    }
    /**
     * @deprecated will be removed in next major version
     *
     * @return array
     */
    public function get_lang_cache($cache_name)
    {
        $cache = Container_Facade::get(Cache_Item_Pool_Interface::class);
        if (!$cache->has_item($cache_name)) {
            return null;
        }
        return $cache->get_item($cache_name)->get();
    }
    /**
     * @deprecated will be removed in next major version
     */
    public function set_lang_cache($cache_name, $lang_cache)
    {
        $cache = Container_Facade::get(Tag_Aware_Cache_Interface::class);
        $cache->get($cache_name, function (Item_Interface $item) use ($lang_cache) {
            $item->tag('oxid_esales.cache.language');
            return $lang_cache;
        });
        return true;
    }
    /**
     * Checks if url has ending slash / - if not, adds it
     *
     * @param string $sUrl url string
     *
     * @return string
     */
    public function check_url_ending_slash($s_url)
    {
        if (!Str::get_str()->preg_match("/\\/\$/", $s_url)) {
            $s_url .= '/';
        }
        return $s_url;
    }
    /**
     * handler for 404 (page not found) error
     *
     * @param string $sUrl url which was given, can be not specified in some cases
     */
    public function handle_page_not_found_error($s_url = ''): void
    {
        $this->set_header('HTTP/1.0 404 Not Found');
        $this->set_header('Content-Type: text/html; charset=UTF-8');
        $s_return = 'Page not found.';
        $o_view = ox_new(\Oxid_Esales\Eshop\Application\Controller\Frontend_Controller::class);
        $o_view->init();
        $o_view->render();
        $o_view->set_class_key('oxUBase');
        $o_view->add_tpl_param('sUrl', $s_url);
        if ($s_ret = Registry::get_utils_view()->get_template_output('message/err_404', $o_view)) {
            $s_return = $s_ret;
        }
        $this->show_message_and_exit($s_return);
    }
    /**
     * Extracts domain name from given host
     *
     * @param string $sHost host name
     *
     * @return string
     */
    public function extract_domain($s_host)
    {
        $o_str = Str::get_str();
        if (!$o_str->preg_match('/[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}/', $s_host) && ($i_last_dot = strrpos($s_host, '.')) !== false) {
            $i_len = $o_str->strlen($s_host);
            if (($i_next_dot = strrpos($s_host, '.', ($i_len - $i_last_dot + 1) * -1)) !== false) {
                $s_host = trim((string) $o_str->substr($s_host, $i_next_dot), '.');
            }
        }
        return $s_host;
    }
    private function is_seo_enabled(): bool
    {
        return (bool) Container_Facade::get_parameter('oxid_esales.seo_mode');
    }
    private function is_seo_disabled_for_shop_and_language(int $shop_id, int $language_id): bool
    {
        $seo_modes = Registry::get_config()->getconfig_param('aSeoModes');
        return is_array($seo_modes) && isset($seo_modes[$shop_id][$language_id]) && !$seo_modes[$shop_id][$language_id];
    }
}