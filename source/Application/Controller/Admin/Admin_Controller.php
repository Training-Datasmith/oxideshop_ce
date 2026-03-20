<?php

declare (strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace Oxid_Esales\Eshop_Community\Application\Controller\Admin;

use Oxid_Esales\Eshop\Core\Registry;
use Oxid_Esales\Eshop\Core\Table_View_Name_Generator;
use Oxid_Esales\Eshop_Community\Core\Di\Container_Facade;
use Oxid_Esales\Eshop_Community\Internal\Framework\File_System\Validator\File_Validator_Bridge_Interface;
/**
 * Main Controller class for admin area.
 */
class Admin_Controller extends \Oxid_Esales\Eshop\Core\Controller\Base_Controller
{
    /**
     * Fixed types - enums in database.
     *
     * @var array
     */
    protected $_a_sum_type = [0 => 'abs', 1 => '%', 2 => 'itm'];
    /**
     * Current class template name.
     *
     * @var string
     */
    protected $_s_this_template;
    /**
     * Override this in list class to show other tab from beginning
     * (default 0 - the first tab).
     *
     * @var int
     */
    protected $_i_def_edit = 0;
    /**
     * Navigation tree object
     *
     * @var \OxidEsales\Eshop\Application\Controller\Admin\NavigationTree
     */
    protected static $_o_navi_tree;
    /**
     * Objects editing language (default 0).
     *
     * @var integer
     */
    protected $_i_edit_lang = 0;
    /**
     * Active shop title
     *
     * @var string
     */
    protected $_s_shop_title = ' - ';
    /**
     * Session user rights
     *
     * @var string
     */
    protected static $_s_auth_user_rights;
    /**
     * Active shop object
     *
     * @return
     */
    protected $_o_edit_shop;
    /**
     * Editable object id
     *
     * @var string
     */
    protected $_s_edit_object_id;
    /**
     * Optional view id.
     *
     * @var string
     */
    protected $view_id;
    /**
     * Creates oxshop object and loads shop data, sets title of shop
     */
    public function __construct()
    {
        $my_config = Registry::get_config();
        $my_config->set_config_param('blAdmin', true);
        $this->set_admin_mode(true);
        if ($o_shop = $this->get_edit_shop($my_config->get_shop_id())) {
            // passing shop info
            $this->_s_shop_title = $o_shop->oxshops__oxname->get_raw_value();
        }
    }
    /**
     * Returns (cached) shop object
     *
     * @param object $sShopId shop id
     *
     * @return \OxidEsales\Eshop\Application\Model\Shop
     */
    protected function get_edit_shop($s_shop_id)
    {
        if (!$this->_o_edit_shop) {
            $this->_o_edit_shop = Registry::get_config()->get_active_shop();
            if ($this->_o_edit_shop->get_id() != $s_shop_id) {
                $o_edit_shop = ox_new(\Oxid_Esales\Eshop\Application\Model\Shop::class);
                if ($o_edit_shop->load($s_shop_id)) {
                    $this->_o_edit_shop = $o_edit_shop;
                }
            }
        }
        return $this->_o_edit_shop;
    }
    /**
     * Sets some shop configuration parameters (such as language),
     * creates some list object (depends on subclass) and executes
     * parent method parent::Init().
     */
    public function init(): void
    {
        // authorization check
        if (!$this->authorize()) {
            Registry::get_utils()->redirect('index.php?cl=login', true, 302);
            exit('Authorization error occurred!');
        }
        $o_lang = Registry::get_lang();
        // language handling
        $this->_i_edit_lang = $o_lang->get_edit_language();
        $o_lang->set_base_language();
        parent::init();
        $this->_a_view_data['malladmin'] = Registry::get_session()->get_variable('malladmin');
    }
    /**
     * Sets global parameters (such as self link, etc.) and returns modified shop object.
     *
     * @param object $oShop Object to modify some parameters
     *
     * @return object
     */
    public function add_global_params($o_shop = null)
    {
        $my_config = Registry::get_config();
        $o_lang = Registry::get_lang();
        $o_shop = parent::add_global_params($o_shop);
        if (Container_Facade::get_parameter('oxid_esales.shop_admin_url')) {
            $url = Container_Facade::get_parameter('oxid_esales.shop_admin_url');
        } else {
            $url = Container_Facade::get_parameter('oxid_esales.shop_url') . $my_config->get_config_param('sAdminDir') . '/';
        }
        $o_view_conf = $this->get_view_config();
        $o_view_conf->set_view_config_param('selflink', Registry::get_utils_url()->process_url($url . 'index.php?editlanguage=' . $this->_i_edit_lang, false));
        $o_view_conf->set_view_config_param('ajaxlink', str_replace('&amp;', '&', Registry::get_utils_url()->process_url($url . 'oxajax.php?editlanguage=' . $this->_i_edit_lang, false)));
        // set language of admin backend
        $this->_a_view_data['adminlang'] = $o_lang->get_tpl_language();
        $this->_a_view_data['charset'] = $this->get_char_set();
        //setting active currency object
        $this->_a_view_data['oActCur'] = $my_config->get_act_shop_currency_object();
        return $o_shop;
    }
    /**
     * Returns service url protocol: "https" is admin works in ssl mode, "http" if no ssl
     *
     * @return string
     */
    protected function get_service_protocol()
    {
        return Registry::get_config()->is_ssl() ? 'https' : 'http';
    }
    /**
     * Sets-up navigation parameters
     *
     * @param string $sNode active view id
     */
    protected function setup_navigation($s_node)
    {
        // navigation according to class
        if ($s_node) {
            $my_admin_navig = $this->get_navigation();
            // active tab
            $i_act_tab = Registry::get_request()->get_request_escaped_parameter('actedit');
            $i_act_tab = $i_act_tab ?: $this->_i_def_edit;
            $s_act_tab = $i_act_tab ? "&actedit={$i_act_tab}" : '';
            // store navigation history
            $this->add_navigation_history($s_node);
            // list url
            $this->_a_view_data['listurl'] = $my_admin_navig->get_list_url($s_node) . $s_act_tab;
            // edit url
            $this->_a_view_data['editurl'] = $my_admin_navig->get_edit_url($s_node, $i_act_tab) . $s_act_tab;
        }
    }
    protected function add_navigation_history(string $node_id): void
    {
        $utils_server = Registry::get_utils_server();
        $history_cookie = $utils_server->get_ox_cookie('oxidadminhistory');
        $history = is_string($history_cookie) ? explode('|', $history_cookie) : [];
        if (!in_array($node_id, $history, true)) {
            $history[] = $node_id;
        }
        $utils_server->set_ox_cookie('oxidadminhistory', implode('|', $history));
    }
    /** @inheritdoc */
    public function render()
    {
        $s_return = parent::render();
        $my_config = Registry::get_config();
        $o_lang = Registry::get_lang();
        // sets up navigation data
        $this->setup_navigation(Registry::get_config()->get_request_controller_id());
        // active object id
        $s_ox_id = $this->get_edit_object_id();
        $this->_a_view_data['oxid'] = !$s_ox_id ? -1 : $s_ox_id;
        // add Sumtype to all templates
        $this->_a_view_data['sumtype'] = $this->_a_sum_type;
        // active shop title
        $this->_a_view_data['actshop'] = $this->_s_shop_title;
        $this->_a_view_data['shopid'] = $my_config->get_shop_id();
        // loading active shop
        if ($s_act_shop_id = Registry::get_session()->get_variable('actshop')) {
            // load object
            $this->_a_view_data['actshopobj'] = $this->get_edit_shop($s_act_shop_id);
        }
        // add language data to all templates
        $this->_a_view_data['actlang'] = $i_language = $o_lang->get_base_language();
        $this->_a_view_data['editlanguage'] = $this->_i_edit_lang;
        $this->_a_view_data['languages'] = $o_lang->get_language_array($i_language);
        // setting maximum upload size
        [$this->_a_view_data['iMaxUploadFileSize'], $this->_a_view_data['sMaxFormattedFileSize']] = $this->get_max_upload_file_info(@ini_get('upload_max_filesize'));
        // "save-on-tab"
        if (!isset($this->_a_view_data['updatelist'])) {
            $this->_a_view_data['updatelist'] = Registry::get_request()->get_request_escaped_parameter('updatelist');
        }
        return $s_return;
    }
    /**
     * Returns maximum allowed size of upload file and formatted size equivalent
     *
     * @param string $maxFileSize recommended maximum size of file (normalu value is taken from php ini, otherwise sets 2MB)
     * @param bool $isFormatted Return formated
     *
     * @return array
     */
    protected function get_max_upload_file_info($max_file_size, $is_formatted = false)
    {
        $max_file_size = $max_file_size ? trim($max_file_size) : '2M';
        // processing config
        $int_max_file_size = (int) $max_file_size;
        $s_param = strtolower($max_file_size[strlen($max_file_size) - 1]);
        switch ($s_param) {
            case 'g':
                $int_max_file_size *= 1024;
            // no break
            case 'm':
                $int_max_file_size *= 1024;
            // no break
            case 'k':
                $int_max_file_size *= 1024;
        }
        // formatting
        $markers = ['KB', 'MB', 'GB'];
        $s_formatted_max_size = '';
        $size = floor($int_max_file_size / 1024);
        while ($size && current($markers)) {
            $s_formatted_max_size = $size . ' ' . current($markers);
            $size = floor($size / 1024);
            next($markers);
        }
        return [$int_max_file_size, $s_formatted_max_size];
    }
    /**
     * Clears cache
     */
    public function save(): void
    {
        $this->reset_content_cache();
    }
    /**
     * Reset output cache
     *
     * @param bool $blForceReset if true, forces reset
     */
    public function reset_content_cache($bl_force_reset = null): void
    {
        $bl_delete_cache_on_logout = Registry::get_config()->get_config_param('blClearCacheOnLogout');
        if (!$bl_delete_cache_on_logout || $bl_force_reset) {
            Registry::get_utils()->ox_reset_file_cache();
        }
    }
    /**
     * Resets counters values from cache. Resets price category articles, category articles,
     * vendor articles, manufacturer articles count.
     *
     * @param string $sCounterType counter type
     * @param string $sValue       reset value
     */
    public function reset_counter($s_counter_type, $s_value = null): void
    {
        $bl_delete_cache_on_logout = Registry::get_config()->get_config_param('blClearCacheOnLogout');
        $my_utils_count = Registry::get_utils_count();
        if (!$bl_delete_cache_on_logout) {
            switch ($s_counter_type) {
                case 'priceCatArticle':
                    $my_utils_count->reset_price_cat_article_count($s_value);
                    break;
                case 'catArticle':
                    $my_utils_count->reset_cat_article_count($s_value);
                    break;
                case 'vendorArticle':
                    $my_utils_count->reset_vendor_article_count($s_value);
                    break;
                case 'manufacturerArticle':
                    $my_utils_count->reset_manufacturer_article_count($s_value);
                    break;
            }
            $this->reset_content_cache_after_reset_counter();
        }
    }
    /**
     * Resets cache.
     */
    protected function reset_content_cache_after_reset_counter()
    {
    }
    /**
     * Checks if current $sUserId user is not an admin and checks if user is able to be edited by logged in user.
     * This method does not perform full rights check.
     *
     * @param string $sUserId user id
     *
     * @return bool
     */
    protected function allow_admin_edit($s_user_id)
    {
        return true;
    }
    /**
     * Get english country name by country iso alpha 2 code
     *
     * @param string $sCountryCode Country code
     *
     * @return boolean
     */
    protected function get_country_by_code($s_country_code)
    {
        //default country
        $s_country = 'international';
        if (!empty($s_country_code)) {
            $a_lang_ids = Registry::get_lang()->get_language_ids();
            $i_english_id = array_search('en', $a_lang_ids);
            if (false !== $i_english_id) {
                $table_view_name_generator = ox_new(Table_View_Name_Generator::class);
                $s_view_name = $table_view_name_generator->get_view_name('oxcountry', $i_english_id);
                $s_q = "select oxtitle from {$s_view_name} where oxisoalpha2 = :oxisoalpha2";
                // Value does not change that often, reading from slave is ok here (see ESDEV-3804 and ESDEV-3822).
                $s_country_name = \Oxid_Esales\Eshop\Core\Database_Provider::get_db()->get_one($s_q, ['oxisoalpha2' => $s_country_code]);
                if ($s_country_name) {
                    $s_country = $s_country_name;
                }
            } else {
                // handling when english language is deleted
                return match ($s_country_code) {
                    'de' => 'germany',
                    default => 'international',
                };
            }
        }
        return strtolower((string) $s_country);
    }
    /**
     * performs authorization of admin user
     *
     * @return boolean
     */
    protected function authorize()
    {
        $session = Registry::get_session();
        return $session->check_session_challenge() && count(Registry::get_utils_server()->get_ox_cookie()) && Registry::get_utils()->check_access_rights();
    }
    /**
     * Returns navigation object
     *
     * @return \OxidEsales\Eshop\Application\Controller\Admin\NavigationTree
     */
    public function get_navigation()
    {
        if (self::$_o_navi_tree == null) {
            self::$_o_navi_tree = ox_new(\Oxid_Esales\Eshop\Application\Controller\Admin\Navigation_Tree::class);
        }
        return self::$_o_navi_tree;
    }
    /**
     * Current view ID getter helps to identify navigation position
     *
     * @return string
     */
    public function get_view_id()
    {
        $view_id = is_null($this->view_id) ? strtolower((string) $this->get_controller_key()) : $this->view_id;
        return $this->get_navigation()->get_class_id($view_id);
    }
    /**
     * Changing active shop
     */
    public function chshp(): void
    {
        $s_act_shop = Registry::get_request()->get_request_escaped_parameter('shp');
        Registry::get_session()->set_variable('shp', $s_act_shop);
        Registry::get_session()->set_variable('currentadminshop', $s_act_shop);
    }
    /**
     * Marks seo entires as expired.
     *
     * @param string $sShopId Shop id
     */
    public function reset_seo_data($s_shop_id): void
    {
        $a_types = ['oxarticle', 'oxcategory', 'oxvendor', 'oxcontent', 'dynamic', 'oxmanufacturer'];
        $o_encoder = Registry::get_seo_encoder();
        foreach ($a_types as $s_type) {
            $o_encoder->mark_as_expired(null, $s_shop_id, 1, null, "oxtype = '{$s_type}'");
        }
    }
    /**
     * Returns id which is used for product preview in shop during administration
     *
     * @return string
     */
    public function get_preview_id()
    {
        return Registry::get_utils()->get_preview_id();
    }
    /**
     * Returns active/editable object id
     *
     * @return string
     */
    public function get_edit_object_id()
    {
        if (null === $s_id = $this->_s_edit_object_id) {
            if (null === $s_id = Registry::get_request()->get_request_escaped_parameter('oxid')) {
                $s_id = Registry::get_session()->get_variable('saved_oxid');
            }
        }
        return $s_id;
    }
    /**
     * Sets editable object id
     *
     * @param string $sId object id
     */
    public function set_edit_object_id($s_id): void
    {
        $this->_s_edit_object_id = $s_id;
        $this->_a_view_data['updatelist'] = 1;
    }
    /**
     * Returns true if editable object is new.
     *
     * @return bool
     */
    protected function is_new_edit_object()
    {
        return '-1' === (string) $this->get_edit_object_id();
    }
    /**
     * Get controller key also for chain extended class.
     *
     * @return null|string
     */
    protected function get_controller_key()
    {
        $actual_class = static::class;
        $controller_key = Registry::get_controller_class_name_resolver()->get_id_by_class_name($actual_class);
        if (is_null($controller_key)) {
            //we might not have found a class key because class is a module chain extended class
            return Registry::get_controller_class_name_resolver()->get_id_by_class_name($this->get_shop_parent_class());
        }
        return $controller_key;
    }
    /**
     * Method to figure out \OxidEsales\Eshop class.
     *
     * @return string
     */
    protected function get_shop_parent_class()
    {
        $class_name = static::class;
        //actual class, might be shop class chain extended by module
        while ($class_name && !\Oxid_Esales\Eshop\Core\Namespace_Information_Provider::class_belongs_to_shop_unified_namespace($class_name)) {
            $class_name = get_parent_class($class_name);
        }
        return $class_name;
    }
    //The current method would be better suited in a form validator.
    protected function validate_request_images(): bool
    {
        if (empty($_FILES['myfile'])) {
            return true;
        }
        $file_validator = Container_Facade::get(File_Validator_Bridge_Interface::class);
        foreach ($_FILES['myfile']['tmp_name'] as $file) {
            if (!$file_validator->validate_image($file)) {
                return false;
            }
        }
        return true;
    }
}